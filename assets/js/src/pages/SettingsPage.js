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
  FormControl,
  InputLabel,
  Select,
  MenuItem,
} from '@mui/material';
import { __ } from '@wordpress/i18n';
import { useOptions, useUpdateOptions, useFieldVisibility } from '../hooks/useOptions';
import { UpgradeToProCard } from '../components';

const PROVIDER_OPTIONS = [
  { value: 'openai', label: 'OpenAI', apiKeyKey: 'openai_api_key', apiKeyLabel: 'OpenAI API Key', helperText: 'Enter your OpenAI API key to enable AI alt text generation.', apiKeyUrl: 'https://platform.openai.com/settings/organization/api-keys', apiKeyUrlLabel: 'Create an API key' },
  { value: 'gemini', label: 'Google Gemini', apiKeyKey: 'gemini_api_key', apiKeyLabel: 'Gemini API Key', helperText: 'Enter your Google Gemini API key to enable AI alt text generation.', apiKeyUrl: 'https://aistudio.google.com/apikey', apiKeyUrlLabel: 'Get an API key' },
  { value: 'claude', label: 'Anthropic Claude', apiKeyKey: 'claude_api_key', apiKeyLabel: 'Claude API Key', helperText: 'Enter your Anthropic Claude API key to enable AI alt text generation.', apiKeyUrl: 'https://console.anthropic.com/settings/keys', apiKeyUrlLabel: 'Get an API key' },
];

/**
 * Settings page component
 */
const SettingsPage = () => {
  const [localSettings, setLocalSettings] = useState({});
  const [isInitialized, setIsInitialized] = useState(false);
  const currentProvider = localSettings.provider || 'openai';
  const currentProviderConfig = PROVIDER_OPTIONS.find(p => p.value === currentProvider) || PROVIDER_OPTIONS[0];
  const apiKeyFieldName = currentProviderConfig.apiKeyKey;

  const { data: options, isLoading: optionsLoading } = useOptions();
  const { data: fieldVisibility, isLoading: visibilityLoading } = useFieldVisibility(apiKeyFieldName);
  const updateOptionsMutation = useUpdateOptions();

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

  // When API key is the server placeholder, show a friendly mask so we never send the real key.
  // Backend returns _api_key_placeholder; if the field equals that, we display dots and send placeholder on save (backend keeps existing key).
  const apiKeyPlaceholder = localSettings._api_key_placeholder;
  const currentApiKeyValue = localSettings[currentProviderConfig.apiKeyKey];
  const displayApiKeyValue = (apiKeyPlaceholder && currentApiKeyValue === apiKeyPlaceholder)
    ? '••••••••••••'
    : (currentApiKeyValue || '');

  const handleSettingChange = (key) => (event) => {
    let newValue = event.target.value;
    // API key fields: if user is editing the masked dots, treat as placeholder (unchanged) or clear.
    const isApiKeyField = PROVIDER_OPTIONS.some(p => p.apiKeyKey === key);
    if (isApiKeyField && apiKeyPlaceholder) {
      if (newValue === '') {
        // User cleared the field.
      } else if (/^•+$/.test(newValue)) {
        // Still only mask characters (e.g. backspace from full mask); keep as placeholder so save doesn't overwrite.
        newValue = apiKeyPlaceholder;
      }
      // Otherwise newValue is the new key they typed.
    }
    setLocalSettings(prev => ({
      ...prev,
      [key]: newValue
    }));
  };

  const handleSave = async () => {
    try {
      const result = await updateOptionsMutation.mutateAsync(localSettings);
      // Sync local state from response so we keep masked keys and _api_key_placeholder in sync.
      if (result?.data && typeof result.data === 'object') {
        setLocalSettings(result.data);
      }
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
            <FormControl fullWidth variant="outlined">
              <InputLabel id="provider-label">{__('AI Provider', 'flux-ai-media-alt-creator')}</InputLabel>
              <Select
                labelId="provider-label"
                value={currentProvider}
                onChange={handleSettingChange('provider')}
                label={__('AI Provider', 'flux-ai-media-alt-creator')}
              >
                {PROVIDER_OPTIONS.map((opt) => (
                  <MenuItem key={opt.value} value={opt.value}>{__(opt.label, 'flux-ai-media-alt-creator')}</MenuItem>
                ))}
              </Select>
            </FormControl>
            <Collapse in={shouldShowApiKeyField}>
              <Box>
                <TextField
                  fullWidth
                  label={__(currentProviderConfig.apiKeyLabel, 'flux-ai-media-alt-creator')}
                  type="password"
                  value={displayApiKeyValue}
                  onChange={handleSettingChange(currentProviderConfig.apiKeyKey)}
                  variant="outlined"
                  helperText={__(currentProviderConfig.helperText, 'flux-ai-media-alt-creator')}
                  placeholder={apiKeyPlaceholder ? __('Enter new key to replace existing', 'flux-ai-media-alt-creator') : ''}
                />
                <FormHelperText sx={{ mt: 1 }}>
                  {__('Your API key is stored securely and never shared.', 'flux-ai-media-alt-creator')}
                  {' '}
                  <Link
                    href={currentProviderConfig.apiKeyUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    sx={{ textDecoration: 'none' }}
                  >
                    {__(currentProviderConfig.apiKeyUrlLabel, 'flux-ai-media-alt-creator')}
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

