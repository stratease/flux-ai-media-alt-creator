/**
 * API service for Flux AI Media Alt Creator WordPress plugin using WordPress apiFetch
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
   * @param {string} endpoint - The API endpoint (should include namespace)
   * @param {Object} options - Request options
   * @returns {Promise} - API response
   */
  async request(endpoint, options = {}) {
    // Ensure endpoint starts with namespace
    const path = endpoint.startsWith(this.namespace) ? endpoint : `${this.namespace}${endpoint}`;
    
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

  // Images endpoints
  async getImages(page = 1, perPage = 20, search = '') {
    const params = new URLSearchParams();
    params.append('page', page.toString());
    params.append('per_page', perPage.toString());
    if (search) {
      params.append('search', search);
    }
    
    return this.request(`/images?${params.toString()}`);
  }

  async getImage(imageId) {
    return this.request(`/images/${imageId}`);
  }

  async triggerScan() {
    return this.request('/images/scan', {
      method: 'POST',
    });
  }

  // Alt text endpoints
  async generateAltText(imageIds, async = false) {
    return this.request('/alt-text/generate', {
      method: 'POST',
      body: JSON.stringify({ image_ids: imageIds, async }),
    });
  }

  async applyAltText(imageIds) {
    return this.request('/alt-text/apply', {
      method: 'POST',
      body: JSON.stringify({ image_ids: imageIds }),
    });
  }

  async batchGenerateAltText(imageIds, batchSize = 10) {
    return this.request('/alt-text/batch-generate', {
      method: 'POST',
      body: JSON.stringify({ image_ids: imageIds, batch_size: batchSize }),
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

