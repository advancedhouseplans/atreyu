import html2canvas from 'html2canvas-pro';

const MAX = 2 * 1024 * 1024;

export async function snapshot() {
    const canvas = await html2canvas(document.documentElement, { logging: false, useCORS: true });
    let blob = await toBlob(canvas, 0.85);
    if (blob.size > MAX) blob = await toBlob(canvas, 0.7);
    return blob;
}

function toBlob(canvas, quality) {
    return new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
}
