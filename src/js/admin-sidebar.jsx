// bring in your admin styles
import '../scss/admin.scss';

import { select, dispatch, subscribe } from '@wordpress/data';

function doGenerate() {
    const sel       = window.BCAdverts.wrapperSelector || '.ad-image';
    const container = document.querySelector(sel);
    if (!container) {
      return alert(`❌ Couldn’t find element for selector "${sel}"`);
    }
    const html = container.outerHTML;
  
    fetch(window.BCAdverts.ajax_url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action:  'bc_generate_advert_image',
        nonce:   window.BCAdverts.nonce,
        post_id: window.BCAdverts.post_id,
        html,
      }),
    })
      .then((r) => r.json())
      .then((json) => {
        if (json.success) {
          alert('✅ Image generated!');
        } else {
          alert('❌ ' + json.data.message);
        }
      })
      .catch((err) => {
        console.error(err);
        alert('❌ Unexpected error');
      });
  }

// 2️⃣ Auto-run after a save+reload
if (sessionStorage.getItem('bcad_generate_after_save')) {
    sessionStorage.removeItem('bcad_generate_after_save');
    // allow ACF to re-render
    setTimeout(doGenerate, 500);
  }
  
  // 3️⃣ Handle click on the Generate button in the editor overlay
  document.addEventListener('click', (e) => {
    if (e.target.id === 'bcad-generate-btn') {
      // flag for post-save flow
      sessionStorage.setItem('bcad_generate_after_save', '1');
  
      // trigger the WP post save
      dispatch('core/editor').savePost();
  
      // once saving finishes, reload the editor to re-render ACF preview
      const unsubscribe = subscribe(() => {
        const saving     = select('core/editor').isSavingPost();
        const autosaving = select('core/editor').isAutosavingPost();
        if (!saving && !autosaving) {
          unsubscribe();
          window.location.reload();
        }
      });
    }
  });




// ————————————————————————————————————————————
//  Classic-screen “Generate” button logic (from admin.js)
// ————————————————————————————————————————————
// document.addEventListener('DOMContentLoaded', () => {
//   const button      = document.querySelector('#bcad-generate-btn');
//   const imageOutput = document.querySelector('#bc-image-output');
//   console.log('🖼️ Advert Image Generator initialized for post ID:', BCAdverts.post_id);

//   if (!button) return;

//   button.addEventListener('click', () => {
//     const selector = BCAdverts.wrapperSelector || '.ad-image';
//     const container = document.querySelector(selector);
//     if (!container) {
//       const msg = `Error: No element found for selector "${selector}".`;
//       console.error(msg);
//       imageOutput.textContent = msg;
//       return;
//     }

//     const html = container.innerHTML;
//     fetch(BCAdverts.css_url)
//       .then(res => res.text())
//       .then(css => {
//         const fd = new FormData();
//         fd.append('action', 'bc_generate_advert_image');
//         fd.append('nonce',   BCAdverts.nonce);
//         fd.append('post_id', BCAdverts.post_id);
//         fd.append('html',    html);
//         fd.append('css',     css);

//         return fetch(BCAdverts.ajax_url, { method: 'POST', body: fd });
//       })
//       .then(res => res.json())
//       .then(data => {
//         if (data.success) {
//           imageOutput.innerHTML = `<img src="${data.data.url}" style="max-width:100%;" />`;
//         } else {
//           console.error('HCTI Error:', data.data.message);
//           imageOutput.textContent = 'Generation failed: ' + data.data.message;
//         }
//       })
//       .catch(err => {
//         console.error('Network/Error generating image:', err);
//         imageOutput.textContent = 'Error: ' + err.message;
//       });
//   });
// });


// ————————————————————————————————————————————
//  Gutenberg sidebar panel (from editor-sidebar.jsx)
// ————————————————————————————————————————————
// import { registerPlugin, unregisterPlugin } from '@wordpress/plugins';
// import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
// import { Button }                  from '@wordpress/components';
// import { useSelect }               from '@wordpress/data';

// const PLUGIN_NAME = 'bc-adverts-sidebar-panel';

// const BCAdvertsSidebarPanel = () => {
//   const postType = useSelect( select => select('core/editor').getCurrentPostType(), [] );
//   if (postType !== 'advert') return null;

//   const html   = document.querySelector('.ad-image')?.outerHTML || '<div>No .ad-image element found</div>';
//   const postId = BCAdverts.post_id;

//   const handleClick = () => {
//     fetch(BCAdverts.ajax_url, {
//       method: 'POST',
//       headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
//       body: new URLSearchParams({
//         action: 'bc_generate_advert_image',
//         nonce:   BCAdverts.nonce,
//         html,
//         post_id: postId,
//       }),
//     })
//       .then(res => res.json())
//       .then(res => {
//         if (res.success) {
//           alert('✅ Image created!');
//         } else {
//           alert('❌ Error: ' + res.data.message);
//         }
//       })
//       .catch(err => {
//         console.error('❌ Error during image generation:', err);
//         alert('❌ Unexpected error.');
//       });
//   };

//   return (
//     <PluginDocumentSettingPanel name="bc-adverts-panel" title="BC Adverts Tools">
//       <Button isPrimary onClick={handleClick}>
//         Generate Image
//       </Button>
//     </PluginDocumentSettingPanel>
//   );
// };

// if ( wp.plugins.getPlugin( PLUGIN_NAME ) ) {
//   unregisterPlugin( PLUGIN_NAME );
// }
// registerPlugin( PLUGIN_NAME, {
//   render: BCAdvertsSidebarPanel,
//   icon:   null,
// });
