import React from 'react';
import {
  Grid,
  Typography,
  Box,
  Paper,
  Skeleton,
  Alert,
  Button,
  Stack,
  Tooltip,
} from '@mui/material';
import { PlayArrow, InfoOutlined } from '@mui/icons-material';
import { __ } from '@wordpress/i18n';
import { useNavigate } from 'react-router-dom';
import { useUsage } from '../hooks/useUsage';
import { useMedia } from '../hooks/useMedia';
import { UpgradeToProCard } from '../components';

/**
 * Overview page component showing usage statistics and summary
 */
const OverviewPage = () => {
  const navigate = useNavigate();
  const { data: usage, isLoading: usageLoading } = useUsage();
  const { data: mediaData, isLoading: mediaLoading } = useMedia(1, 1);

  const isProActive = typeof window !== 'undefined' && window.fluxAIMediaAltCreatorAdmin?.isProActive == true;

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

  const providerLabel = usage?.provider_display_label || __('OpenAI (gpt-4o-mini)', 'flux-ai-media-alt-creator');

  return (
    <Box>
      <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5, flexWrap: 'wrap' }}>
        <Typography variant="h5" gutterBottom sx={{ mb: 0 }}>
          {__('Usage Statistics', 'flux-ai-media-alt-creator')}
        </Typography>
        {!isProActive && (
          <Tooltip title={__('Cost estimates are based on the active AI provider: ', 'flux-ai-media-alt-creator') + providerLabel} arrow placement="top">
            <InfoOutlined fontSize="small" color="action" sx={{ cursor: 'help' }} />
          </Tooltip>
        )}
      </Box>

      <Grid container spacing={3} sx={{ mt: 2 }}>
        {/* Media without alt - top, out of card, with Start processing button */}
        <Grid item xs={12}>
          <Stack
            direction={{ xs: 'column', sm: 'row' }}
            spacing={2}
            alignItems={{ xs: 'stretch', sm: 'center' }}
            justifyContent="space-between"
            sx={{
              p: 2,
              borderRadius: 1,
              bgcolor: 'action.hover',
              border: '1px solid',
              borderColor: 'divider',
            }}
          >
            <Box>
              {mediaLoading ? (
                <Skeleton variant="text" width={120} height={48} />
              ) : (
                <>
                  <Typography variant="h4" color="primary" component="span">
                    {formatNumber(mediaData?.total || 0)}
                  </Typography>
                  <Typography variant="body1" color="text.secondary" component="span" sx={{ ml: 1 }}>
                    {__('Media Files Without Alt Text', 'flux-ai-media-alt-creator')}
                  </Typography>
                </>
              )}
            </Box>
            <Button
              variant="contained"
              color="primary"
              startIcon={<PlayArrow />}
              onClick={() => navigate('/media')}
              sx={{ flexShrink: 0 }}
            >
              {__('Start Processing', 'flux-ai-media-alt-creator')}
            </Button>
          </Stack>
        </Grid>

        {isProActive && (
          <Grid item xs={12}>
            <Alert severity="info">
              {__('Pro is enabled. Token-based processing is not used—alt text generation uses your Flux Suite license.', 'flux-ai-media-alt-creator')}
            </Alert>
          </Grid>
        )}

        {!isProActive && (
          <>
            {/* API Usage - token usage and pricing (provider-specific cost calculations) */}
            <Grid item xs={12} lg={6}>
              <Grid container spacing={2}>
                <Grid item xs={12}>
                  <Typography variant="subtitle1" fontWeight={600} gutterBottom>
                    {__('API Usage', 'flux-ai-media-alt-creator')}
                  </Typography>
                </Grid>
                <Grid item xs={12} sm={4}>
                  <Paper sx={{ p: 2, textAlign: 'center' }}>
                    {usageLoading ? (
                      <Skeleton variant="text" width="60%" height={40} sx={{ mx: 'auto' }} />
                    ) : (
                      <>
                        <Typography variant="h5" color="primary">
                          {formatNumber(usage?.requests_count || 0)}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                          {__('Requests This Month', 'flux-ai-media-alt-creator')}
                        </Typography>
                      </>
                    )}
                  </Paper>
                </Grid>
                <Grid item xs={12} sm={4}>
                  <Paper sx={{ p: 2, textAlign: 'center' }}>
                    {usageLoading ? (
                      <Skeleton variant="text" width="60%" height={40} sx={{ mx: 'auto' }} />
                    ) : (
                      <>
                        <Typography variant="h5" color="primary">
                          {usage?.tokens_used > 0 ? formatNumber(usage.tokens_used) : '—'}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                          {__('Tokens Used', 'flux-ai-media-alt-creator')}
                        </Typography>
                      </>
                    )}
                  </Paper>
                </Grid>
                <Grid item xs={12} sm={4}>
                  <Paper sx={{ p: 2, textAlign: 'center' }}>
                    {usageLoading ? (
                      <Skeleton variant="text" width="60%" height={40} sx={{ mx: 'auto' }} />
                    ) : (
                      <>
                        <Typography variant="h5" color="primary">
                          {formatCurrency(usage?.cost_estimate || 0)}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                          {__('Estimated Cost', 'flux-ai-media-alt-creator')}
                        </Typography>
                      </>
                    )}
                  </Paper>
                </Grid>
                {usage?.last_reset_date && (
                  <Grid item xs={12}>
                    <Typography variant="caption" color="text.secondary">
                      {__('Usage last reset:', 'flux-ai-media-alt-creator')}{' '}
                      {new Date(usage.last_reset_date + ' UTC').toLocaleDateString()}
                    </Typography>
                  </Grid>
                )}
              </Grid>
            </Grid>

            {/* Upgrade to Pro - half width on large screens */}
            <Grid item xs={12} lg={6}>
              <UpgradeToProCard variant="overview" showCaption={false} />
            </Grid>
          </>
        )}
      </Grid>
    </Box>
  );
};

export default OverviewPage;
