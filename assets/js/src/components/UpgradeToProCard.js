import React from 'react';
import {
  Card,
  CardContent,
  Stack,
  Box,
  Typography,
  Button,
  Divider,
} from '@mui/material';
import { Star, CheckCircle } from '@mui/icons-material';
import { __ } from '@wordpress/i18n';

const OVERVIEW_FEATURES = [
  __('No per-token fees—flat rate includes all usage', 'flux-ai-media-alt-creator'),
  __('Automated processing for new and existing media', 'flux-ai-media-alt-creator'),
  __('Access to all Flux Suite premium plugins', 'flux-ai-media-alt-creator'),
  __('No OpenAI API key required—single Flux Suite license', 'flux-ai-media-alt-creator'),
];

const SETTINGS_FEATURES = [
  __('Additional AI media meta updates (Alt Text, Title, Description, Caption and more...)', 'flux-ai-media-alt-creator'),
  __('Recurring automated processing of existing media', 'flux-ai-media-alt-creator'),
  __('Access to all Flux Suite premium plugins', 'flux-ai-media-alt-creator'),
  __('No OpenAI API key required - works with a single Flux Suite license', 'flux-ai-media-alt-creator'),
];

/**
 * Reusable Upgrade to Pro upsell card.
 *
 * @param {Object}   props           Component props.
 * @param {string}   props.variant   'overview' | 'settings' - Changes intro text and feature list.
 * @param {boolean}  [props.showCaption] Whether to show "Requires Flux Suite license" caption. Default true for settings.
 */
const UpgradeToProCard = ({ variant = 'settings', showCaption = variant === 'settings' }) => {
  const isOverview = variant === 'overview';
  const introText = isOverview
    ? __('Skip token-based fees. Get a flat rate with premium features across all Flux Suite plugins:', 'flux-ai-media-alt-creator')
    : __('Get more powerful features with Flux AI Media Alt Creator Pro:', 'flux-ai-media-alt-creator');
  const features = isOverview ? OVERVIEW_FEATURES : SETTINGS_FEATURES;

  return (
    <Card
      variant="outlined"
      sx={{
        border: '1px solid',
        borderColor: 'primary.main',
        backgroundColor: 'action.hover',
        height: '100%',
      }}
    >
      <CardContent>
        <Stack spacing={2}>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            <Star sx={{ color: 'primary.main' }} />
            <Typography variant="h6" component="h3">
              {__('Upgrade to Pro', 'flux-ai-media-alt-creator')}
            </Typography>
          </Box>

          <Typography variant="body2" color="text.secondary">
            {introText}
          </Typography>

          <Stack spacing={1}>
            {features.map((text, i) => (
              <Box key={i} sx={{ display: 'flex', alignItems: 'flex-start', gap: 1 }}>
                <CheckCircle sx={{ fontSize: '1.2rem', color: 'success.main', mt: 0.25 }} />
                <Typography variant="body2">
                  {text}
                </Typography>
              </Box>
            ))}
          </Stack>

          <Divider />

          <Button
            variant="contained"
            color="primary"
            href="https://fluxplugins.com/ai-media-alt-creator-pro/"
            target="_blank"
            rel="noopener noreferrer"
            fullWidth
            sx={{ fontWeight: 600 }}
          >
            {__('Learn More About Pro', 'flux-ai-media-alt-creator')}
          </Button>

          {showCaption && (
            <Typography variant="caption" color="text.secondary" sx={{ textAlign: 'center', display: 'block' }}>
              {__('Requires Flux Suite license', 'flux-ai-media-alt-creator')}
            </Typography>
          )}
        </Stack>
      </CardContent>
    </Card>
  );
};

export default UpgradeToProCard;
