-- Configuración base de Pagifier: países y tipos de campaña.
--
-- Sin estas dos tablas la portada no pinta ningún botón de país ni el
-- selector de tipo de tráfico. Se aplica sobre un esquema recién creado:
--   mysql -u root pagifier < db/schema.sql
--   mysql -u root pagifier < db/seed_config.sql
--
-- El contenido (domains y landings) no se versiona.

/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` (`id`, `slug`, `button_name`, `title`, `flag_image`, `sub_countries`, `sort_order`) VALUES (1,'czech','Czech Republic','CZECH REPUBLIC / CHEQUIA','cz.png','',1),(2,'denmark','Denmark','DENMARK / DINAMARCA','denmark.png','',2),(3,'netherlands','Dutch countries','DUTCH COUNTRIES / PAÍSES HOLANDESES','netherlands.png','Nederland, België',3),(4,'uk','English countries','ENGLISH COUNTRIES / PAÍSES INGLESES','uk.png','Australia, New Zealand, Canada, Ireland, Malta, Singapore, UK, US',4),(5,'finland','Finland','FINLAND / FINLANDIA','finland.png','',5),(6,'france','French countries','FRENCH COUNTRIES / PAÍSES FRANCESES','france.png','France, Canada, Belgique, Luxembourg, Suisse',6),(7,'germany','German countries','GERMAN COUNTRIES / PAÍSES ALEMANES','germany.png','Deutschland, Schweiz, Österreich',7),(8,'greece','Greece','GREECE / GRECIA','greece.png','',8),(9,'italy','Italy','ITALY / ITALIA','italy.png','',9),(10,'norway','Norway','NORWAY / NORUEGA','norway.png','',10),(11,'poland','Poland','POLAND / POLONIA','poland.png','',11),(12,'portuguese','Portuguese countries','PORTUGUESE COUNTRIES / PAÍSES PORTUGUESES','portuguese.png','Portugal, Brasil',12),(13,'spain','Spanish countries','SPANISH COUNTRIES / PAÍSES ESPAÑOLES','spain.png','España, Chile, México',13),(14,'sweden','Sweden','SWEDEN / SUECIA','sweden.png','',14);
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `campaign_types` WRITE;
/*!40000 ALTER TABLE `campaign_types` DISABLE KEYS */;
INSERT INTO `campaign_types` (`id`, `code`, `label`, `url_params`, `sort_order`, `active`, `created_at`, `updated_at`) VALUES (1,'cpm','CPM','add=BckBtn&s1={INPUT}&s2=campaign&s3=source&s4=s4&tracking_id=clickid',1,1,'2026-02-13 11:35:29','2026-02-16 06:59:42'),(2,'cpl','CPL','add=BckBtn&s1={INPUT}&s2=affId&tracking_id=clickid',2,1,'2026-02-13 11:35:29','2026-02-16 10:42:13'),(3,'chart','CHART','add=BckBtn&s1={INPUT}&s2=source&tracking_id=clickid',3,1,'2026-02-13 11:35:29','2026-02-16 10:42:13'),(4,'sem','BING','s1={INPUT}&s2={Campaign}&s3={AdGroup}&s4={keyword}-{MatchType}&tracking_id={msclkid}',4,1,'2026-02-13 11:35:29','2026-02-16 10:42:13'),(5,'adw','ADWORDS','s1={INPUT}&s2={_campaigname}&s3={_adgroupname}&s4={keyword}-{matchtype}&tracking_id={gclid}',5,1,'2026-02-13 11:35:29','2026-02-16 10:42:13');
/*!40000 ALTER TABLE `campaign_types` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

