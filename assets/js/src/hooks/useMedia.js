import { useQuery } from '@tanstack/react-query';
import { apiService } from '../services/api';

/**
 * React Query hook for fetching media files without alt text
 * 
 * @param {number} page - Page number (1-based)
 * @param {number} perPage - Number of items per page
 * @param {string} search - Search term
 * @param {object} filters - Additional search filters (extensible via hooks)
 */
export const useMedia = (page = 1, perPage = 20, search = '', filters = {}) => {
  return useQuery({
    queryKey: ['media', page, perPage, search, filters],
    queryFn: () => apiService.getMedia(page, perPage, search, filters),
    staleTime: 30 * 1000, // 30 seconds
    retry: 2,
  });
};

/**
 * React Query hook for fetching available media type groups
 */
export const useMediaTypeGroups = () => {
  return useQuery({
    queryKey: ['media-type-groups'],
    queryFn: () => apiService.getMediaTypeGroups(),
    staleTime: 5 * 60 * 1000, // 5 minutes
    retry: 2,
  });
};

