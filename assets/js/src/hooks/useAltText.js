import { useMutation, useQueryClient } from '@tanstack/react-query';
import { apiService } from '../services/api';

/**
 * React Query hook for generating alt text
 */
export const useGenerateAltText = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: ({ imageIds, async = false }) => {
      return apiService.generateAltText(imageIds, async);
    },
    onSuccess: () => {
      // Invalidate images query to refresh data
      queryClient.invalidateQueries({ queryKey: ['images'] });
    },
    onError: (error) => {
      console.error('Failed to generate alt text:', error);
    },
  });
};

/**
 * React Query hook for applying alt text
 */
export const useApplyAltText = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: (imageIds) => {
      return apiService.applyAltText(imageIds);
    },
    onSuccess: () => {
      // Invalidate images query to refresh data
      queryClient.invalidateQueries({ queryKey: ['images'] });
    },
    onError: (error) => {
      console.error('Failed to apply alt text:', error);
    },
  });
};

/**
 * React Query hook for batch generating alt text
 */
export const useBatchGenerateAltText = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: ({ imageIds, batchSize = 10 }) => {
      return apiService.batchGenerateAltText(imageIds, batchSize);
    },
    onSuccess: () => {
      // Invalidate images query to refresh data
      queryClient.invalidateQueries({ queryKey: ['images'] });
    },
    onError: (error) => {
      console.error('Failed to schedule batch generation:', error);
    },
  });
};

