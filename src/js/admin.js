import '../scss/admin.scss';

document.addEventListener('DOMContentLoaded', () => {
    const button = document.querySelector('#bc-generate-image');
    const imageOutput = document.querySelector('#bc-image-output');

    if (button) {
        button.addEventListener('click', () => {
            const html = document.querySelector('.ad-image').innerHTML;

            fetch(BCAdverts.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                },
                body: new URLSearchParams({
                    action: 'bc_generate_advert_image',
                    nonce: BCAdverts.nonce,
                    html: html,
                    post_id: BCAdverts.post_id,
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    imageOutput.innerHTML = `<img src="${data.data.url}" style="max-width:100%;" />`;
                } else {
                    alert('Image generation failed: ' + data.data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }
});