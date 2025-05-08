import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

const BCAdvertsSidebarPanel = () => {
    const postType = useSelect(select => select('core/editor').getCurrentPostType(), []);
    const html = document.querySelector('.ad-image')?.outerHTML ?? '<div>No .ad-image element found</div>';
    const postId = BCAdverts.post_id ?? null;

    if (!postId) {
        console.error("⚠️ BCAdverts.post_id is not defined.");
        return null;
    }

    console.log('🔍 Sending HTML and post ID:', postId, html);

    if (postType !== 'advert') {
        console.warn('Post Type is:', postType);
        return (
            <div>
                <h3>Could not detect the "advert" post type</h3>
                <p>Current detected post type: {postType}</p>
            </div>
        );
    }
    

    const handleClick = () => {
        console.log(`🖼️ Generating image for post ID: ${postId}`);
    
        fetch(BCAdverts.ajax_url, {
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
        })
            .then(res => res.json())
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

    // return (
    //     <PluginDocumentSettingPanel
    //         name="bc-adverts-panel"
    //         title="BC Adverts Tools"
    //         className="bc-adverts-sidebar-panel"
    //     >
    //         <Button isPrimary onClick={handleClick}>
    //             Generate Image
    //         </Button>
    //     </PluginDocumentSettingPanel>
    // );
    return (
        <PluginDocumentSettingPanel
            name="bc-adverts-panel"
            title="BC Adverts Tools"
            className="bc-adverts-sidebar-panel"
        >
            <Button isPrimary>
                This is a test button
            </Button>
            <div>🔍 Post Type Detected: {postType}</div>
            <div>🔍 Post ID Detected: {postId}</div>
        </PluginDocumentSettingPanel>
    );
    
};

document.addEventListener('DOMContentLoaded', () => {
    registerPlugin('bc-adverts-sidebar-panel', {
        render: BCAdvertsSidebarPanel,
        icon: null,
    });
});

