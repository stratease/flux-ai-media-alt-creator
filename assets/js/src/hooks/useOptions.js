import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiService } from '../services/api';

/**
 * React Query hook for fetching plugin options
 */
export const useOptions = () => {
  return useQuery({
    queryKey: ['options'],
    queryFn: () => apiService.getOptions(),
    staleTime: 5 * 60 * 1000, // 5 minutes
    retry: 2,
  });
};

/**
 * React Query hook for updating plugin options
 */
export const useUpdateOptions = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: (data) => {
      return apiService.updateOptions(data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['options'] });
    },
    onError: (error) => {
      console.error('Failed to update options:', error);
    },
  });
};

/**
 * React Query hook for checking field visibility
 */
export const useFieldVisibility = (fieldName) => {
  return useQuery({
    queryKey: ['fieldVisibility', fieldName],
    queryFn: () => apiService.getFieldVisibility(fieldName),
    staleTime: 5 * 60 * 1000, // 5 minutes
    retry: 2,
    enabled: !!fieldName,
  });
};

