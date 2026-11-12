import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
const mainNames = ['ebsPrimary','ebsSecondary','ebsTertiary'];
const inputs = mainNames.map((name,i)=>({id:name,name,value:['#33258a','#1d154e','#a2a2a2'][i],main:true}));
inputs.push({id:'ebsHighlightedText',name:'ebsHighlightedText',value:'#ffffff'});
for (const x of inputs) x.classList={contains:n=>n==='ebs_mainColorPicker' && x.main};
const styles={},listeners={};
const form={querySelectorAll:()=>inputs,addEventListener:(name,fn)=>listeners[name]=fn,dispatchEvent:e=>listeners[e.type]?.(e)};
const button={dataset:{nonce:'test'},addEventListener:(name,fn)=>listeners['button:'+name]=fn};
const status={textContent:''};
const document={getElementById:id=>id==='colorPickForm'?form:id==='ebs-import-theme-colors'?button:id==='ebs-import-status'?status:inputs.find(x=>x.id===id),querySelectorAll:()=>[]};
function wrap(nodes=[]){
 const w={
  ready:fn=>fn($),click:()=>w,slideToggle:()=>w,toggleClass:()=>w,hasClass:()=>false,
  addClass:()=>w,removeClass:()=>w,first:()=>w,closest:()=>w,
  find:selector=>selector.startsWith('#')?$(selector):w,
  attr:(name,value)=>value===undefined?nodes[0]?.[name]:w,
  text:value=>value===undefined?'Label':w,
  val(value){if(value===undefined)return nodes[0]?.value;for(const n of nodes)n.value=value;return w;},
  each(fn){nodes.forEach(n=>fn.call(n));return w;},
  css(key,value){if(nodes[0]==='root')styles[key]=value;return w;},
  wpColorPicker(option,value){
   if(typeof option==='object')for(const n of nodes)n.picker=option;
   else if(option==='color')for(const n of nodes){
    // WordPress/Iris callback may precede its input's value update.
    n.picker.change.call(n,{}, {color:{toString:()=>value}}); n.value=value;
   }
   return w;
  },
 };
 return w;
}
function $(s){
 if(typeof s==='function'){s($);return;}
 if(typeof s==='object')return wrap([s]);
 if(s===':root')return wrap(['root']);
 if(s.startsWith('#'))return wrap(inputs.filter(x=>x.id===s.slice(1)));
 if(s==='.ebs_mainColorPicker, .ebs_colorPicker')return wrap(inputs);
 if(s==='.ebs_mainColorPicker')return wrap(inputs.filter(x=>x.main));
 if(s==='.ebs_colorPicker'||s==='.ebs_advanced_settings input[type=text]')return wrap(inputs.filter(x=>!x.main));
 return wrap();
}
const imported={ebsPrimary:'#33258a',ebsSecondary:'#1d154e',ebsTertiary:'#837ac9',ebsHighlightedText:'#ffffff'};
const context=vm.createContext({jQuery:$,document,window:{addEventListener(){}},wp:{i18n:{__:s=>s}},Event:class{constructor(type){this.type=type;}},ajaxurl:'/ajax',URLSearchParams,fetch:async()=>({ok:true,json:async()=>({success:true,data:imported})})});
for(const file of ['ebsMenuPage.js','theme-import.js'])vm.runInContext(readFileSync(new URL('../resources/'+file,import.meta.url),'utf8'),context);
await listeners['button:click']();
assert.equal(styles['--ebsTertiary'],imported.ebsTertiary,'Preview must use final imported tertiary, not previous picker value');
assert.equal(styles['--ebsHighlightedText'],'#ffffff','Server-provided advanced values must survive final synchronization');
const tertiary=inputs[2];
tertiary.picker.change.call(tertiary,{}, {color:{toString:()=>'#009bdc'}});
assert.equal(styles['--ebsTertiary'],'#009bdc','Manual picker change must preview the event color before Iris updates the input');
console.log('Import and manual picker preview synchronization passed');
