// Get CSRF token from meta tag
function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

export async function startMultipart(filename, contentType, size, prefix = 'job-originals') {
  const res = await fetch('/uploads/multipart/start', {
    method: 'POST',
    headers: { 
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken()
    },
    body: JSON.stringify({ filename, contentType, size, prefix })
  });
  if (!res.ok) throw new Error('Failed to start multipart');
  return await res.json(); // { key, uploadId }
}

export async function signPart(key, uploadId, partNumber) {
  const res = await fetch('/uploads/multipart/sign-part', {
    method: 'POST',
    headers: { 
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken()
    },
    body: JSON.stringify({ key, uploadId, partNumber })
  });
  if (!res.ok) throw new Error('Failed to sign part');
  return await res.json(); // { url }
}

export async function completeMultipart(key, uploadId, parts, jobId, originalFilename, retries = 3) {
  for (let attempt = 1; attempt <= retries; attempt++) {
    try {
      console.log(`Attempting multipart completion (attempt ${attempt}/${retries})`, {
        key, uploadId, partsCount: parts.length, jobId
      });

      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 120000); // 120 second timeout for large files

      const res = await fetch('/uploads/multipart/complete', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken()
        },
        body: JSON.stringify({ key, uploadId, parts, job_id: jobId, original_filename: originalFilename }),
        signal: controller.signal
      });

      clearTimeout(timeoutId);

      if (!res.ok) {
        const errorText = await res.text();
        console.error(`Multipart completion failed (attempt ${attempt}):`, {
          status: res.status,
          statusText: res.statusText,
          error: errorText
        });
        
        if (attempt === retries) {
          throw new Error(`Failed to complete multipart: ${res.status} ${res.statusText} - ${errorText}`);
        }
        
        // Wait before retry (exponential backoff)
        await new Promise(resolve => setTimeout(resolve, attempt * 1000));
        continue;
      }

      const result = await res.json();
      console.log('Multipart completion successful:', result);
      return result;

    } catch (error) {
      console.error(`Multipart completion error (attempt ${attempt}):`, error);
      
      if (attempt === retries) {
        // If this is the last attempt and we're getting a timeout/network error,
        // but the upload might have actually succeeded, we should check if the file exists
        console.warn('Multipart completion failed after all retries, but upload may have succeeded');
        throw new Error(`Failed to complete multipart after ${retries} attempts: ${error.message}`);
      }
      
      // Wait before retry (exponential backoff)
      await new Promise(resolve => setTimeout(resolve, attempt * 1000));
    }
  }
}

export async function abortMultipart(key, uploadId) {
  await fetch('/uploads/multipart/abort', {
    method: 'POST',
    headers: { 
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken()
    },
    body: JSON.stringify({ key, uploadId })
  });
}

export async function uploadFileInParts({ file, jobId, chunkSize = 10 * 1024 * 1024, onProgress }) {
  const { key, uploadId } = await startMultipart(file.name, file.type || 'application/pdf', file.size);

  const totalParts = Math.ceil(file.size / chunkSize);
  const parts = [];
  let uploadedBytes = 0;

  for (let partNumber = 1; partNumber <= totalParts; partNumber++) {
    const start = (partNumber - 1) * chunkSize;
    const end = Math.min(start + chunkSize, file.size);
    const blob = file.slice(start, end);

    const { url } = await signPart(key, uploadId, partNumber);
    const putRes = await fetch(url, { method: 'PUT', body: blob });
    if (!putRes.ok) throw new Error(`Failed to upload part ${partNumber}`);
    const eTag = putRes.headers.get('ETag')?.replaceAll('"', '') || '';
    parts.push({ ETag: eTag, PartNumber: partNumber });

    uploadedBytes += blob.size;
    if (onProgress) onProgress({ loaded: uploadedBytes, total: file.size, partNumber, totalParts });
  }

  try {
    const completion = await completeMultipart(key, uploadId, parts, jobId, file.name);
    return { key, uploadId, parts, completion };
  } catch (error) {
    console.error('Multipart upload failed during completion:', error);
    
    // Even if completion failed, the file might have been uploaded successfully
    // We should try to abort the multipart upload to clean up
    try {
      await abortMultipart(key, uploadId);
      console.log('Successfully aborted multipart upload after completion failure');
    } catch (abortError) {
      console.error('Failed to abort multipart upload:', abortError);
    }
    
    throw new Error(`Multipart upload completion failed: ${error.message}`);
  }
}


