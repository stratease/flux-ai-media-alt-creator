import { useQuery } from '@tanstack/react-query';
import { apiService } from '../services/api';

/**
 * React Query hook for fetching usage statistics
 */
export const useUsage = () => {
  return useQuery({
    queryKey: ['usage'],
    queryFn: () => apiService.getUsage(),
    staleTime: 60 * 1000, // 1 minute
    retry: 2,
  });
};

