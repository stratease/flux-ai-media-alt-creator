import React, { useState, useEffect } from 'react';
import {
  Box,
  Typography,
  TextField,
  Button,
  Alert,
  Stack,
  FormHelperText,
  Skeleton,
  Collapse,
  Link,
  Grid,
} from '@mui/material';
import { __ } from '@wordpress/i18n';
import { useOptions, useUpdateOptions, useFieldVisibility } from '../hooks/useOptions';
import { UpgradeToProCard } from '../components';

/**
 * Settings page component
 */
const SettingsPage = () => {
  const { data: options, isLoading: optionsLoading } = useOptions();
  const { data: fieldVisibility, isLoading: visibilityLoading } = useFieldVisibility('openai_api_key');
  const updateOptionsMutation = useUpdateOptions();
  
  const [localSettings, setLocalSettings] = useState({});
  const [isInitialized, setIsInitialized] = useState(false);

  // Initialize local settings from server data
  useEffect(() => {
    if (!isInitialized && options && typeof options === 'object' && Object.keys(options).length > 0) {
      setLocalSettings(options);
      setIsInitialized(true);
    }
  }, [options, isInitialized]);

  const shouldShowApiKeyField = fieldVisibility?.should_show !== false;
  
  // Check if Pro version is active - hides Pro upsell message when active.
  const isProActive = typeof window !== 'undefined' && window.fluxAIMediaAltCreatorAdmin?.isProActive == true;

  const handleSettingChange = (key) => (event) => {
    const newValue = event.target.value;
    setLocalSettings(prev => ({
      ...prev,
      [key]: newValue
    }));
  };

  const handleSave = async () => {
    try {
      await updateOptionsMutation.mutateAsync(localSettings);
    } catch (error) {
      console.error('Failed to save settings:', error);
    }
  };

  if (optionsLoading || visibilityLoading) {
    return <Skeleton variant="rectangular" height={400} />;
  }

  return (
    <Box>
      <Typography variant="h5" gutterBottom>
        {__('Settings', 'flux-ai-media-alt-creator')}
      </Typography>

      <Grid container spacing={3} sx={{ mt: 2 }}>
        {/* Settings Column - 6 columns on large screens, 12 on mobile */}
        <Grid item xs={12} lg={6}>
          <Stack spacing={3}>
            <Collapse in={shouldShowApiKeyField}>
              <Box>
                <TextField
                  fullWidth
                  label={__('OpenAI API Key', 'flux-ai-media-alt-creator')}
                  type="password"
                  value={localSettings.openai_api_key || ''}
                  onChange={handleSettingChange('openai_api_key')}
                  variant="outlined"
                  helperText={__('Enter your OpenAI API key to enable AI alt text generation.', 'flux-ai-media-alt-creator')}
                />
                <FormHelperText sx={{ mt: 1 }}>
                  {__('Your API key is stored securely and never shared.', 'flux-ai-media-alt-creator')}
                  {' '}
                  <Link
                    href="https://platform.openai.com/settings/organization/api-keys"
                    target="_blank"
                    rel="noopener noreferrer"
                    sx={{ textDecoration: 'none' }}
                  >
                    {__('Create an API key', 'flux-ai-media-alt-creator')}
                  </Link>
                </FormHelperText>
              </Box>
            </Collapse>

            {updateOptionsMutation.isSuccess && (
              <Alert severity="success">
                {__('Settings saved successfully.', 'flux-ai-media-alt-creator')}
              </Alert>
            )}

            {updateOptionsMutation.isError && (
              <Alert severity="error">
                {__('Failed to save settings. Please try again.', 'flux-ai-media-alt-creator')}
              </Alert>
            )}

            <Button
              variant="contained"
              onClick={handleSave}
              disabled={updateOptionsMutation.isPending}
            >
              {updateOptionsMutation.isPending ? __('Saving...', 'flux-ai-media-alt-creator') : __('Save Settings', 'flux-ai-media-alt-creator')}
            </Button>
          </Stack>
        </Grid>

        {/* Pro Version Upsell Column - 6 columns on large screens, 12 on mobile - Hidden when Pro is active */}
        {!isProActive && (
          <Grid item xs={12} lg={6}>
            <UpgradeToProCard variant="settings" showCaption />
          </Grid>
        )}
      </Grid>
    </Box>
  );
};

export default SettingsPage;

