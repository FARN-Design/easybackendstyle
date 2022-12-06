//Initial loading Hash
let calcHash = 0;
//Check if form was submitted
let formSubmit = false;

//----------------Generate initial hash----------------

const loadingElements = document.querySelectorAll('[type="color"]');
loadingElements.forEach(getHash)

function getHash(item){
    calcHash = generateHash(item.value) ^ calcHash;
}
console.log("Loading Hash: "+ calcHash)

//----------------Hash function----------------

function generateHash(string){
    let hash = 0;
    let char;

    for (let i = 0; i < string.length; i++) {
        char = string.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
    }

    return Math.abs(hash);
}

//----------------Check form submit----------------

var submit = document.getElementById('submit');
submit.onclick = function() {
    formSubmit = true
}
var resetDefaults = document.getElementById('resetDefaults');
resetDefaults.onclick = function() {
    formSubmit = true
}

//----------------Check for change before leaving site----------------

window.onbeforeunload = function(){
    let newHash = 0;
    const currentElements = document.querySelectorAll('[type="color"]');

    currentElements.forEach(newGetHash)

    function newGetHash(item){
        newHash = newHash ^ generateHash(item.value)
    }
    console.log("Exiting Hash: "+newHash)

    if (newHash !== calcHash && !formSubmit){
        return 'Are you sure you want to leave?';
    }
};

