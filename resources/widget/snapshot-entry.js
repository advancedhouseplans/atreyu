import html2canvas from 'html2canvas-pro';

const MAX_BYTES = 2 * 1024 * 1024;

globalThis.AtreyuSnapshot = {
    async capture() {
        const canvas = await html2canvas(document.documentElement, {
            logging: false,
            useCORS: true,
        });
        let blob = await toBlob(canvas, 0.85);
        if (blob.size > MAX_BYTES) blob = await toBlob(canvas, 0.7);
        return blob;
    },
};

function toBlob(canvas, quality) {
    return new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
}
