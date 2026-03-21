/**
 * API service for Flux AI Alt Text & Accessibility Audit WordPress plugin using WordPress apiFetch
 */

import apiFetch from '@wordpress/api-fetch';

class ApiService {
  constructor() {
    this.namespace = 'flux-ai-media-alt-creator/v1';
    
    // Configure apiFetch with proper API root
    const apiRoot = window.fluxAIMediaAltCreatorAdmin?.apiUrl || '/wp-json/';
    apiFetch.use(apiFetch.createRootURLMiddleware(apiRoot));
  }

  /**
   * Make a request using WordPress apiFetch
   * @param {string} endpoint - The API endpoint (will be prefixed with namespace)
   * @param {Object} options - Request options
   * @returns {Promise} - API response
   */
  async request(endpoint, options = {}) {
    // Prepend namespace if not already included
    let path = endpoint;
    if (!endpoint.startsWith(`/${this.namespace}/`) && !endpoint.startsWith(this.namespace)) {
      // Ensure endpoint starts with /
      const cleanEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
      path = `/${this.namespace}${cleanEndpoint}`;
    }
    
    const defaultOptions = {
      path: path,
      method: 'GET',
      headers: {
        'X-WP-Nonce': window.fluxAIMediaAltCreatorAdmin?.nonce || '',
        'Content-Type': 'application/json',
      },
    };

    const mergedOptions = {
      ...defaultOptions,
      ...options,
      headers: {
        ...defaultOptions.headers,
        ...(options.headers || {}),
      },
    };

    try {
      const response = await apiFetch(mergedOptions);
      
      // Handle the structured response format
      if (response && typeof response === 'object' && response.success !== undefined) {
        return response.data;
      }
      
      return response;
    } catch (error) {
      // Throw the error for React Query to handle
      throw error;
    }
  }

  // Media endpoints
  async getMedia(page = 1, perPage = 20, search = '', filters = {}) {
    const params = new URLSearchParams();
    params.append('page', page.toString());
    params.append('per_page', perPage.toString());
    if (search) {
      params.append('search', search);
    }
    if (filters && Object.keys(filters).length > 0) {
      params.append('filters', JSON.stringify(filters));
    }
    
    return this.request(`/media?${params.toString()}`);
  }

  async getMediaItem(mediaId) {
    return this.request(`/media/${mediaId}`);
  }

  async getMediaTypeGroups() {
    return this.request('/media/type-groups');
  }

  async triggerScan() {
    return this.request('/media/scan', {
      method: 'POST',
    });
  }

  // Alt text endpoints
  async generateAltText(mediaIds, async = false) {
    return this.request('/alt-text/generate', {
      method: 'POST',
      body: JSON.stringify({ media_ids: mediaIds, async }),
    });
  }

  async applyAltText(mediaIds, altTexts = {}) {
    return this.request('/alt-text/apply', {
      method: 'POST',
      body: JSON.stringify({ 
        media_ids: mediaIds,
        alt_texts: altTexts,
      }),
    });
  }

  async batchGenerateAltText(mediaIds, batchSize = 10) {
    return this.request('/alt-text/batch-generate', {
      method: 'POST',
      body: JSON.stringify({ media_ids: mediaIds, batch_size: batchSize }),
    });
  }

  // Options endpoints
  async getOptions() {
    return this.request('/options');
  }

  async updateOptions(options) {
    return this.request('/options', {
      method: 'POST',
      body: JSON.stringify({ options }),
    });
  }

  async getFieldVisibility(fieldName) {
    return this.request(`/field-visibility?field_name=${encodeURIComponent(fieldName)}`);
  }

  // Usage endpoints
  async getUsage() {
    return this.request('/usage');
  }

  // Compliance endpoints
  async getComplianceSummary() {
    return this.request('/compliance/summary');
  }

  async runComplianceScan() {
    return this.request('/compliance/scan', {
      method: 'POST',
    });
  }

  /**
   * Set compliance alt category for media. Use 'decorative' to mark as decorative; use '' to re-evaluate and assign category from current alt (e.g. unmark).
   * @param {number[]} mediaIds Attachment IDs.
   * @param {string} altCategory 'decorative' or '' (reclassify).
   */
  async setAltCategory(mediaIds, altCategory) {
    return this.request('/compliance/set-category', {
      method: 'POST',
      body: JSON.stringify({ media_ids: mediaIds, alt_category: altCategory == null ? '' : String(altCategory) }),
    });
  }

}

// Export singleton instance
export const apiService = new ApiService();
export default apiService;

