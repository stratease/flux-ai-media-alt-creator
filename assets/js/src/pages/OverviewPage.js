import React from 'react';
import { Grid, Typography, Box, Paper, Skeleton } from '@mui/material';
import { __ } from '@wordpress/i18n';
import { useUsage } from '../hooks/useUsage';
import { useImages } from '../hooks/useImages';

/**
 * Overview page component showing usage statistics and summary
 */
const OverviewPage = () => {
  const { data: usage, isLoading: usageLoading } = useUsage();
  const { data: imagesData, isLoading: imagesLoading } = useImages(1, 1);

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 2,
      maximumFractionDigits: 4,
    }).format(amount);
  };

  const formatNumber = (num) => {
    return new Intl.NumberFormat('en-US').format(num);
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>
        {__('Usage Statistics', 'flux-ai-media-alt-creator')}
      </Typography>

      <Grid container spacing={3} sx={{ mt: 2 }}>
        {/* Requests Count */}
        <Grid item xs={12} sm={6} md={3}>
          <Paper sx={{ p: 2, textAlign: 'center' }}>
            {usageLoading ? (
              <Skeleton variant="text" width="60%" height={40} sx={{ mx: 'auto' }} />
            ) : (
              <>
                <Typography variant="h4" color="primary">
                  {formatNumber(usage?.requests_count || 0)}
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  {__('Requests This Month', 'flux-ai-media-alt-creator')}
                </Typography>
              </>
            )}
          </Paper>
        </Grid>

        {/* Tokens Used */}
        <Grid item xs={12} sm={6} md={3}>
          <Paper sx={{ p: 2, textAlign: 'center' }}>
            {usageLoading ? (
              <Skeleton variant="text" width="60%" height={40} sx={{ mx: 'auto' }} />
            ) : (
              <>
                <Typography variant="h4" color="primary">
                  {usage?.tokens_used > 0 ? formatNumber(usage.tokens_used) : '—'}
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  {__('Tokens Used', 'flux-ai-media-alt-creator')}
                </Typography>
              </>
            )}
          </Paper>
        </Grid>

        {/* Estimated Cost */}
        <Grid item xs={12} sm={6} md={3}>
          <Paper sx={{ p: 2, textAlign: 'center' }}>
            {usageLoading ? (
              <Skeleton variant="text" width="60%" height={40} sx={{ mx: 'auto' }} />
            ) : (
              <>
                <Typography variant="h4" color="primary">
                  {formatCurrency(usage?.cost_estimate || 0)}
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  {__('Estimated Cost', 'flux-ai-media-alt-creator')}
                </Typography>
              </>
            )}
          </Paper>
        </Grid>

        {/* Images Without Alt Text */}
        <Grid item xs={12} sm={6} md={3}>
          <Paper sx={{ p: 2, textAlign: 'center' }}>
            {imagesLoading ? (
              <Skeleton variant="text" width="60%" height={40} sx={{ mx: 'auto' }} />
            ) : (
              <>
                <Typography variant="h4" color="primary">
                  {formatNumber(imagesData?.total || 0)}
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  {__('Images Without Alt Text', 'flux-ai-media-alt-creator')}
                </Typography>
              </>
            )}
          </Paper>
        </Grid>
      </Grid>

      {usage?.last_reset_date && (
        <Box sx={{ mt: 3 }}>
          <Typography variant="body2" color="text.secondary">
            {__('Last reset:', 'flux-ai-media-alt-creator')} {new Date(usage.last_reset_date + ' UTC').toLocaleDateString()}
          </Typography>
        </Box>
      )}
    </Box>
  );
};

export default OverviewPage;

