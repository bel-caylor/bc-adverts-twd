import '../scss/admin.scss';
import { select, dispatch, subscribe } from '@wordpress/data';

/**
 * Generate the advert image by sending HTML and CSS to the server.
 */
async function generateAdvertImage() {
  // Get the wrapper element containing the advert markup.
  const selector = window.BCAdverts.wrapperSelector;
  const container = document.querySelector(selector);
  if (!container) {
    alert(`❌ Couldn’t find element for selector "${selector}"`);
    return;
  }

  // Extract the HTML to send.
  const htmlFragment = container.outerHTML;

  try {
    // Fetch the CSS needed to style the advert.
    const cssResponse = await fetch(window.BCAdverts.css_url);
    const cssText = await cssResponse.text();

    // Build the form data payload.
    const formData = new FormData();
    formData.append('action', 'bc_generate_advert_image');
    formData.append('nonce', window.BCAdverts.nonce);
    formData.append('post_id', window.BCAdverts.post_id);
    formData.append('css', cssText);
    formData.append('html', htmlFragment);

    // Send the request to generate the image.
    const response = await fetch(window.BCAdverts.ajax_url, {
      method: 'POST',
      body: formData,
    });
    const result = await response.json();

    // Notify the user of success or error.
    if (result.success) {
      alert('✅ Image generated!');
    } else {
      alert(`❌ ${result.data.message}`);
    }
  } catch (error) {
    console.error(error);
    alert('❌ Unexpected error occurred');
  }
}

// Auto-run after saving the post and reloading.
if (sessionStorage.getItem('bcad_generate_after_save')) {
  sessionStorage.removeItem('bcad_generate_after_save');
  // Delay to allow ACF preview to render.
  setTimeout(generateAdvertImage, 500);
}

// Handle click on the Generate button in the editor overlay.
document.addEventListener('click', (event) => {
  if (event.target.id === 'bcad-generate-btn') {
    // Mark to run generation after save.
    sessionStorage.setItem('bcad_generate_after_save', '1');

    // Save the post.
    dispatch('core/editor').savePost();

    // Reload editor once save completes.
    const unsubscribe = subscribe(() => {
      const isSaving = select('core/editor').isSavingPost();
      const isAutosaving = select('core/editor').isAutosavingPost();
      if (!isSaving && !isAutosaving) {
        unsubscribe();
        window.location.reload();
      }
    });
  }
});
