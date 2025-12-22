import { useQuery } from '@tanstack/react-query';
import { apiService } from '../services/api';

/**
 * React Query hook for fetching images without alt text
 */
export const useImages = (page = 1, perPage = 20, search = '') => {
  return useQuery({
    queryKey: ['images', page, perPage, search],
    queryFn: () => apiService.getImages(page, perPage, search),
    staleTime: 30 * 1000, // 30 seconds
    retry: 2,
  });
};

