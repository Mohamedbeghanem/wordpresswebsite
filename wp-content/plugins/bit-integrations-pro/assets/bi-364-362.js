var f=Object.defineProperty,z=Object.defineProperties;var v=Object.getOwnPropertyDescriptors;var I=Object.getOwnPropertySymbols;var E=Object.prototype.hasOwnProperty,k=Object.prototype.propertyIsEnumerable;var y=(e,t,i)=>t in e?f(e,t,{enumerable:!0,configurable:!0,writable:!0,value:i}):e[t]=i,b=(e,t)=>{for(var i in t||(t={}))E.call(t,i)&&y(e,i,t[i]);if(I)for(var i of I(t))k.call(t,i)&&y(e,i,t[i]);return e},g=(e,t)=>z(e,v(t));var j=(e,t,i)=>new Promise((d,m)=>{var r=l=>{try{o(i.next(l))}catch(u){m(u)}},n=l=>{try{o(i.throw(l))}catch(u){m(u)}},o=l=>l.done?d(l.value):Promise.resolve(l.value).then(r,n);o((i=i.apply(e,t)).next())});import{l as D,r as S,j as a}from"./main.js";import{x as N,a8 as U}from"./bi-336-181.js";import{S as F,I as p,E as w,G,A as O}from"./bi-764-943.js";import{h as x,c as T,a as _}from"./bi-596-870.js";import"./bi-171-733.js";function J({notionConf:e,setNotionConf:t,step:i,setStep:d,isInfo:m,loading:r,setLoading:n}){const o=D(N),[l,u]=S.useState(!1),[s,h]=S.useState({clientId:"",clientSecret:""}),A=()=>j(this,null,function*(){document.querySelector(".btcd-s-wrp").scrollTop=0,d(2),n(g(b({},r),{page:!0})),(yield _(e,t))&&n(g(b({},r),{page:!1}))}),R=`
  <h4> Step of get Client Id & Client Secret</h4>
  <ul>
    <li>Goto <a href="https://www.notion.so/my-integrations" target='_blank'>My integrations.</a></li>
    <li>Click new integration.</li>
    <li>Name to identify your integration to users.</li>
    <li><b>User Capabilities</b> always select read user information including email addresses</li>
    <li><b>Submit</b></li>
    <li>Select <b>Integration type</b> Public</li>
    <li>Fill up <b>OAuth Domain & URIs</b> information</li>
    <li>Homepage & Redirect URIs copy from Integration Settings</li>
    <li>Finally, click <b>Authorize</b> button.</li>
</ul>
`;return a.jsxs(F,{step:i,stepNo:1,style:{width:900,height:"auto"},children:[a.jsxs("div",{className:"mt-2",children:[a.jsx(p,{label:"Integration Name",name:"name",placeholder:"Integration Name...",value:e.name,onchange:c=>x(c,e,t,s,h)}),a.jsx(p,{label:"Homepage",copytext:window.location.origin,setToast:!0}),a.jsx(p,{label:"Redirect URIs",copytext:`${o.api.base}/redirect`,setToast:!0}),a.jsx(p,{label:"OAuth client ID",name:"clientId",placeholder:"client ID...",value:e.clientId,onchange:c=>x(c,e,t,s,h)}),a.jsx(w,{error:s.clientId}),a.jsx(p,{label:"OAuth client secret",name:"clientSecret",placeholder:"client secret...",value:e.clientSecret,onchange:c=>x(c,e,t,s,h)}),a.jsx(w,{error:s.clientSecret}),a.jsx(G,{url:"https://www.notion.so/my-integrations",info:"Notion My integrations, please visit"}),!m&&a.jsx(O,{onclick:()=>T(e,t,s,h,u,r,n),nextPage:A,auth:l,loading:r.auth})]}),a.jsx(U,{note:R})]})}export{J as default};
