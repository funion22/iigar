<?php
/**
 * Reordenación de listas con sort_order.
 *
 * Unas tablas ordenan dentro de un grupo y otras de forma global:
 *   domains             -> por country_id
 *   brandless_landings  -> por language_code
 *   landings            -> global
 *   campaign_types      -> global
 *
 * Pasando $scopeCol se delimita el grupo; con null se opera sobre la tabla
 * entera. Así, al insertar un dominio en España no se toca el orden de Suecia.
 *
 * Uso:
 *   $r = sortPrepare($pdo, 'domains', $id, $posPedida, 'country_id', $countryId);
 *   ... UPDATE/INSERT del llamante usando $r['pos'] ...
 *   sortRenumber($pdo, 'domains', 'country_id', $countryId);
 *   if ($r['oldScope'] !== null) sortRenumber($pdo, 'domains', 'country_id', $r['oldScope']);
 */

/** Los nombres de tabla/columna se interpolan en el SQL: solo se admiten estos. */
function sortIdent($name, array $allowed)
{
    if (!in_array($name, $allowed, true)) {
        throw new InvalidArgumentException("Identificador no permitido en sort_order: $name");
    }
    return $name;
}

function sortTable($table)
{
    return sortIdent($table, ['landings', 'domains', 'brandless_landings', 'campaign_types']);
}

function sortScope($col)
{
    return $col === null ? null : sortIdent($col, ['country_id', 'language_code']);
}

/**
 * Renumera sort_order como 1..N dentro del grupo, respetando el orden actual.
 * Cierra los huecos y hace que sort_order coincida con la posición real.
 */
function sortRenumber(PDO $pdo, $table, $scopeCol = null, $scopeVal = null)
{
    $t = sortTable($table);
    $s = sortScope($scopeCol);

    $pdo->exec("SET @pos := 0");
    if ($s === null) {
        $pdo->exec("UPDATE `$t` SET sort_order = (@pos := @pos + 1) ORDER BY sort_order, id");
    } else {
        $pdo->prepare("UPDATE `$t` SET sort_order = (@pos := @pos + 1) WHERE `$s` = ? ORDER BY sort_order, id")
            ->execute([$scopeVal]);
    }
}

/**
 * Abre hueco para dejar la fila en la posición $pos de su grupo.
 * Para altas, $id = 0. Devuelve:
 *   'pos'      => posición final, acotada a una que exista de verdad
 *   'oldScope' => grupo del que sale la fila si ha cambiado de grupo, o null
 *                 (el llamante debe renumerarlo también para cerrar el hueco)
 */
function sortPrepare(PDO $pdo, $table, $id, $pos, $scopeCol = null, $scopeVal = null)
{
    $t = sortTable($table);
    $s = sortScope($scopeCol);
    $pos = (int)$pos;

    // Posición y grupo actuales: sin ellos no se sabe hacia dónde se mueve
    // la fila ni se puede cerrar el hueco que deja atrás.
    $oldOrder = null;
    $oldScope = null;
    if ($id > 0) {
        $cols = 'sort_order' . ($s === null ? '' : ", `$s`");
        $q = $pdo->prepare("SELECT $cols FROM `$t` WHERE id = ?");
        $q->execute([$id]);
        if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            $oldOrder = (int)$row['sort_order'];
            $oldScope = $s === null ? null : $row[$s];
        }
    }

    // ¿Sigue en el mismo grupo? Si cambia de país/idioma es un alta en el destino.
    $sameScope = ($oldOrder !== null)
        && ($s === null || (string)$oldScope === (string)$scopeVal);

    $where  = $s === null ? '' : "WHERE `$s` = ?";
    $params = $s === null ? [] : [$scopeVal];

    $count = $pdo->prepare("SELECT COUNT(*) FROM `$t` $where");
    $count->execute($params);
    $total = (int)$count->fetchColumn();

    // Si la fila ya está en el grupo ocupa una de las plazas; si no, se añade una.
    $pos = max(1, min($pos, $sameScope ? max(1, $total) : $total + 1));

    $scopeSQL = $s === null ? '' : "`$s` = ? AND";

    if ($sameScope) {
        if ($pos < $oldOrder) {
            // Sube: las que quedan en medio bajan un puesto
            $sql = "UPDATE `$t` SET sort_order = sort_order + 1
                    WHERE $scopeSQL sort_order >= ? AND sort_order < ? AND id != ?";
            $pdo->prepare($sql)->execute(array_merge($params, [$pos, $oldOrder, $id]));
        } elseif ($pos > $oldOrder) {
            // Baja: las de en medio suben un puesto, cerrando el hueco que deja
            $sql = "UPDATE `$t` SET sort_order = sort_order - 1
                    WHERE $scopeSQL sort_order > ? AND sort_order <= ? AND id != ?";
            $pdo->prepare($sql)->execute(array_merge($params, [$oldOrder, $pos, $id]));
        }
        // Si no cambia de posición no se toca a nadie.
    } else {
        // Alta en el grupo (fila nueva o que viene de otro país/idioma)
        $sql = "UPDATE `$t` SET sort_order = sort_order + 1
                WHERE $scopeSQL sort_order >= ? AND id != ?";
        $pdo->prepare($sql)->execute(array_merge($params, [$pos, $id]));
    }

    return [
        'pos'      => $pos,
        'oldScope' => ($s !== null && $oldOrder !== null && !$sameScope) ? $oldScope : null,
    ];
}
