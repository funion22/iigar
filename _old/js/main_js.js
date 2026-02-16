const replacers = document.querySelectorAll('.replacer');
const replaceUs = document.querySelectorAll('.replaceMe');


const replaceLinks = (evt) => {
  const button = evt.currentTarget;
  const { replacer } = button.dataset;
  
  replaceUs.forEach((elem) => {
    const wholeString = elem.getAttribute('href');
    const toBeReplaced = elem.dataset.toBeReplaced;
    const stringWithReplacement = wholeString.replace(toBeReplaced, replacer);
    
    elem.dataset.toBeReplaced = replacer;
    elem.setAttribute('href', stringWithReplacement);
    elem.textContent = stringWithReplacement;
  });
}

replacers.forEach((replacer) => replacer.addEventListener('click', replaceLinks))

document.querySelectorAll("button").forEach(function(button) {
button.addEventListener("click", function() {
  	var element = document.getElementById(event.target.id + "1")
    if (element.style.display == "block") {
        element.style.display = "none";
        button.classList.remove('buttonactive');
    } else {
        element.style.display = "block";
        document.getElementById("content-domains").style.display = "block";
        button.classList.add('buttonactive');
    }
    if (document.getElementById("denmark1").style.display == "none" && document.getElementById("finland1").style.display == "none" && document.getElementById("france1").style.display == "none" && document.getElementById("germany1").style.display == "none" && document.getElementById("italy1").style.display == "none" && document.getElementById("netherlands1").style.display == "none" && document.getElementById("norway1").style.display == "none" && document.getElementById("poland1").style.display == "none" && document.getElementById("spain1").style.display == "none" && document.getElementById("sweden1").style.display == "none" && document.getElementById("uk1").style.display == "none" && document.getElementById("portuguese1").style.display == "none" && document.getElementById("czech1").style.display == "none") {
        document.getElementById("content-domains").style.display = "none";
        [].forEach.call(document.querySelectorAll('.contentdomains'), function (el) {
            el.style.display = 'none';
        });
    }
  })
})

document.querySelectorAll("#showcolourcontent").forEach(function(href) {
	href.addEventListener("click", function() {
	    document.getElementById("content-domains").style.display = "block";
	})  
})

var showContents = document.getElementsByClassName("showcontent");

for(var i = 0; i < showContents.length; i++) {
    showContents[i].addEventListener("click", function() {
        [].forEach.call(document.querySelectorAll('.contentdomains'), function (el) {
            el.style.display = 'none';
        });
        var showcontentelement = document.getElementById(event.target.className + "content");
        showcontentelement.style.display = "block";
    })
}

window.addEventListener("DOMContentLoaded", function(e) {
  var links = document.getElementsByTagName("A");
  for(var i=0; i < links.length; i++) {
    if(!links[i].hash) continue;
    if(links[i].origin + links[i].pathname != self.location.href) continue;
    (function(anchorPoint) {
      links[i].addEventListener("click", function(e) {
        anchorPoint.scrollIntoView(true);
        e.preventDefault();
      }, false);
    })(document.getElementById(links[i].hash.replace(/#/, "")));
  }
}, false);