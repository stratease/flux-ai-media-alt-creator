import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiService } from '../services/api';

/**
 * React Query hook for fetching compliance summary.
 */
export const useComplianceSummary = () => {
  return useQuery({
    queryKey: ['compliance', 'summary'],
    queryFn: () => apiService.getComplianceSummary(),
    staleTime: 30 * 1000,
    retry: 2,
  });
};

/**
 * Mutation hook for running compliance scan.
 *
 * @param {Object} options
 * @param {() => void} [options.onScanComplete] - Called when the scan finishes successfully (e.g. to show a Snackbar).
 */
export const useComplianceScan = (options = {}) => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: () => apiService.runComplianceScan(),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['compliance', 'summary'] });
      if (typeof options.onScanComplete === 'function') {
        options.onScanComplete();
      }
    },
  });
};

/**
 * Mutation hook for setting compliance alt category (e.g. mark decorative or reclassify/unmark).
 * @param {Object} options
 * @param {number[]} options.mediaIds Attachment IDs.
 * @param {string} options.altCategory 'decorative' or '' to re-evaluate.
 */
export const useSetAltCategory = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ mediaIds, altCategory }) => apiService.setAltCategory(mediaIds, altCategory),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['media'] });
      queryClient.invalidateQueries({ queryKey: ['compliance', 'summary'] });
    },
  });
};
