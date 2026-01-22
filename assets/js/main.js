document.addEventListener('DOMContentLoaded', () => {
    const btn = document.querySelector('.sfw-button');
    
    if (btn) {
        // Add a slight bounce effect on click before opening
        btn.addEventListener('click', (e) => {
            // Optional: You could add Google Analytics tracking here in the future
            console.log('WhatsApp Button Clicked');
        });
    }
});