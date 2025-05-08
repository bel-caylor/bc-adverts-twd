import{registerPlugin as a}from"@wordpress/plugins";import{PluginDocumentSettingPanel as u}from"@wordpress/edit-post";import{Button as c}from"@wordpress/components";import{useSelect as p}from"@wordpress/data";var i={exports:{}},d={};/**
 * @license React
 * react-jsx-runtime.production.js
 *
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */var v=Symbol.for("react.transitional.element"),m=Symbol.for("react.fragment");function l(s,e,t){var n=null;if(t!==void 0&&(n=""+t),e.key!==void 0&&(n=""+e.key),"key"in e){t={};for(var o in e)o!=="key"&&(t[o]=e[o])}else t=e;return e=t.ref,{$$typeof:v,type:s,key:n,ref:e!==void 0?e:null,props:t}}d.Fragment=m;d.jsx=l;d.jsxs=l;i.exports=d;var r=i.exports;const x=()=>{var n;const s=p(o=>o("core/editor").getCurrentPostType(),[]),e=((n=document.querySelector(".ad-image"))==null?void 0:n.outerHTML)??"<div>No .ad-image element found</div>",t=BCAdverts.post_id??null;return t?(console.log("🔍 Sending HTML and post ID:",t,e),s!=="advert"?(console.warn("Post Type is:",s),r.jsxs("div",{children:[r.jsx("h3",{children:'Could not detect the "advert" post type'}),r.jsxs("p",{children:["Current detected post type: ",s]})]})):r.jsxs(u,{name:"bc-adverts-panel",title:"BC Adverts Tools",className:"bc-adverts-sidebar-panel",children:[r.jsx(c,{isPrimary:!0,children:"This is a test button"}),r.jsxs("div",{children:["🔍 Post Type Detected: ",s]}),r.jsxs("div",{children:["🔍 Post ID Detected: ",t]})]})):(console.error("⚠️ BCAdverts.post_id is not defined."),null)};document.addEventListener("DOMContentLoaded",()=>{a("bc-adverts-sidebar-panel",{render:x,icon:null})});
