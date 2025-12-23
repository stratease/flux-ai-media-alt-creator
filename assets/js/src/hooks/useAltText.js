import { useMutation, useQueryClient } from '@tanstack/react-query';
import { apiService } from '../services/api';

/**
 * React Query hook for generating alt text
 */
export const useGenerateAltText = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: ({ mediaIds, async = false }) => {
      return apiService.generateAltText(mediaIds, async);
    },
    onSuccess: () => {
      // Invalidate media query to refresh data
      queryClient.invalidateQueries({ queryKey: ['media'] });
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
    mutationFn: ({ mediaIds, altTexts = {} }) => {
      return apiService.applyAltText(mediaIds, altTexts);
    },
    onSuccess: () => {
      // Invalidate media query to refresh data
      queryClient.invalidateQueries({ queryKey: ['media'] });
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
    mutationFn: ({ mediaIds, batchSize = 10 }) => {
      return apiService.batchGenerateAltText(mediaIds, batchSize);
    },
    onSuccess: () => {
      // Invalidate media query to refresh data
      queryClient.invalidateQueries({ queryKey: ['media'] });
    },
    onError: (error) => {
      console.error('Failed to schedule batch generation:', error);
    },
  });
};

