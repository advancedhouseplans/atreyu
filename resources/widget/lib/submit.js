export async function submit({ endpoint, token, formData, metadata, snapshotBlob }) {
    formData.set('metadata', JSON.stringify(metadata));
    if (snapshotBlob) formData.set('snapshot', snapshotBlob, 'snapshot.jpg');

    const resp = await fetch(`${endpoint}/api/reports`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token}` },
        body: formData,
    });

    if (!resp.ok) return { ok: false, status: resp.status };

    const json = await resp.json().catch(() => ({}));
    return { ok: true, status: resp.status, report_id: json.report_id };
}
