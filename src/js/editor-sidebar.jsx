import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

console.log('🟢 Editor Sidebar Script Loaded');

const BCAdvertsSidebarPanel = () => {
    console.log('📌 BCAdvertsSidebarPanel is running');
    const postType = useSelect(select => select('core/editor').getCurrentPostType(), []);
    if (postType !== 'advert') {
        return null;
    }
    const html = document.querySelector('.ad-image')?.outerHTML || '<div>No .ad-image element found</div>';
    const postId = BCAdverts.post_id;

    const handleClick = () => {
        console.log(`🖼️ Generating image for post ID: ${postId}`);
        fetch(BCAdverts.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'bc_generate_advert_image',
                nonce: BCAdverts.nonce,
                html: html,
                post_id: postId,
            }),
        })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    alert('✅ Image created!');
                } else {
                    alert('❌ Error: ' + res.data.message);
                }
            })
            .catch(err => {
                console.error('❌ Error during image generation:', err);
                alert('❌ Unexpected error.');
            });
    };

    return (
        <PluginDocumentSettingPanel
            name="bc-adverts-panel"
            title="BC Adverts Tools"
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
