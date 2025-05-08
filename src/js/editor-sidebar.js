import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { Fragment } from '@wordpress/element';


const BCAdvertsSidebarPanel = () => {
    const postType = useSelect(select => select('core/editor').getCurrentPostType(), []);
    const html = document.querySelector('.ad-image')?.outerHTML ?? '<div>No .ad-image element found</div>';
	const postId = BCAdverts.post_id;
    
    console.log('🔍 Sending HTML and post ID:', postId, html);
    
	if (postType !== 'advert') return null;

    const handleClick = () => {
        const postId = BCAdverts.post_id;
    
        fetch(`/wp-json/wp/v2/advert/${postId}`)
            .then(res => res.json())
            .then(post => {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = post.content?.rendered ?? '';
    
                const adImageSection = wrapper.querySelector('.ad-image');
    
                if (!adImageSection) {
                    alert('⚠️ Could not find .ad-image section in post content.');
                    return;
                }
    
                const html = adImageSection.outerHTML;
                console.log('🖼️ Extracted ad-image HTML:', html);
    
                // Now send that to your image generator
                return fetch(BCAdverts.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'bc_generate_advert_image',
                        nonce: BCAdverts.nonce,
                        html: html,
                        post_id: postId,
                    }),
                });
            })
            .then(res => res?.json?.())
            .then(res => {
                if (!res) return;
                if (res.success) {
                    alert('✅ Image created!');
                    console.log('Image URL:', res.data.url);
                } else {
                    alert('❌ Error: ' + res.data.message);
                }
            })
            .catch(err => {
                console.error('❌ Error during image generation:', err);
                alert('❌ Unexpected error. Check console.');
            });
    };
    
    

	return (
		<PluginDocumentSettingPanel
			name="bc-adverts-panel"
			title="BC Adverts Tools"
			className="bc-adverts-sidebar-panel"
		>
			<Button isPrimary onClick={handleClick}>
				Generate Image
			</Button>
		</PluginDocumentSettingPanel>
	);
};

registerPlugin('bc-adverts-sidebar-panel', {
	render: BCAdvertsSidebarPanel,
	icon: null,
});
