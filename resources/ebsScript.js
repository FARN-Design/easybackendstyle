//Initial loading Hash
let calcHash = 0;

//Calculates the initial loading Hash
const loadingElements = document.querySelectorAll('[type="color"]');
loadingElements.forEach(getHash)

function getHash(item){
    calcHash = generateHash(item.value) ^ calcHash;
}

function generateHash(string){
    let hash = 0;
    let char;

    for (let i = 0; i < string.length; i++) {
        char = string.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash;
    }

    return Math.abs(hash);
}

console.log(calcHash)

window.onbeforeunload = function(){
    let newHash = 0;
    const currentElements = document.querySelectorAll('[type="color"]');

    currentElements.forEach(newGetHash)

    function newGetHash(item){
        newHash = newHash ^ generateHash(item.value)
    }
    console.log(newHash)

    if (newHash !== calcHash){
        return 'Are you sure you want to leave?';
    }
};

