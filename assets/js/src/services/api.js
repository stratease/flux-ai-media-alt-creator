/**
 * API service for Flux AI Media Alt Creator WordPress plugin using WordPress apiFetch
 */

import apiFetch from '@wordpress/api-fetch';

class ApiService {
  constructor() {
    // Configure apiFetch with proper API root (already includes namespace)
    const apiRoot = window.fluxAIMediaAltCreatorAdmin?.apiUrl || '/wp-json/';
    apiFetch.use(apiFetch.createRootURLMiddleware(apiRoot));
  }

  /**
   * Make a request using WordPress apiFetch
   * @param {string} endpoint - The API endpoint (without namespace, e.g., '/media')
   * @param {Object} options - Request options
   * @returns {Promise} - API response
   */
  async request(endpoint, options = {}) {
    // apiUrl already includes the namespace, so use endpoint directly
    const defaultOptions = {
      path: endpoint,
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

  async applyAltText(mediaIds) {
    return this.request('/alt-text/apply', {
      method: 'POST',
      body: JSON.stringify({ media_ids: mediaIds }),
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
}

// Export singleton instance
export const apiService = new ApiService();
export default apiService;

