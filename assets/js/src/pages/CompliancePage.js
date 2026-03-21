import React from 'react';
import {
  Box,
  Typography,
  Paper,
  Stack,
  Button,
  Skeleton,
  Grid,
  Link,
  CircularProgress,
  Snackbar,
  Alert,
} from '@mui/material';
import { __ } from '@wordpress/i18n';
import { PlayArrow, CheckCircle } from '@mui/icons-material';
import { useNavigate, Link as RouterLink } from 'react-router-dom';
import { useComplianceSummary, useComplianceScan } from '../hooks/useCompliance';

const WCAG_URL = 'https://www.w3.org/WAI/WCAG21/Understanding/non-text-content';

const CATEGORIES = [
  {
    key: 'missing',
    label: __('Missing', 'flux-ai-media-alt-creator'),
    description: __('No alt text set, or only whitespace. These images fail WCAG 1.1.1 and need descriptive alt or to be marked decorative.', 'flux-ai-media-alt-creator'),
    cta: __('View Missing Images', 'flux-ai-media-alt-creator'),
  },
  {
    key: 'placeholder',
    label: __('Placeholder', 'flux-ai-media-alt-creator'),
    description: __('Generic or low-value alt (e.g. "image", "photo"), filename-based text, or fewer than 3 words. Not meaningful for screen readers or SEO.', 'flux-ai-media-alt-creator'),
    cta: __('View Placeholder', 'flux-ai-media-alt-creator'),
  },
  {
    key: 'duplicate',
    label: __('Duplicate', 'flux-ai-media-alt-creator'),
    description: __('Same exact alt text used on 3 or more images. Consider making each description unique to the image content.', 'flux-ai-media-alt-creator'),
    cta: __('View Duplicates', 'flux-ai-media-alt-creator'),
  },
  {
    key: 'descriptive',
    label: __('Descriptive', 'flux-ai-media-alt-creator'),
    description: __('Alt text has 4+ words and describes the image. Good baseline; can be improved with page or product context.', 'flux-ai-media-alt-creator'),
    cta: __('View Descriptive', 'flux-ai-media-alt-creator'),
  },
  {
    key: 'contextual',
    label: __('Contextual', 'flux-ai-media-alt-creator'),
    description: __('Descriptive alt that also includes parent context (e.g. post title or product name). Best for accessibility and SEO.', 'flux-ai-media-alt-creator'),
    cta: __('View Contextual', 'flux-ai-media-alt-creator'),
  },
  {
    key: 'decorative',
    label: __('Decorative', 'flux-ai-media-alt-creator'),
    description: __('Intentionally empty alt for purely decorative images. Correct per WCAG; no action needed unless the image is meaningful.', 'flux-ai-media-alt-creator'),
    cta: __('View Decorative', 'flux-ai-media-alt-creator'),
  },
];

/**
 * Compliance dashboard: metrics and CTAs to Media page with category filter.
 */
const CompliancePage = () => {
  const navigate = useNavigate();
  const [showScanCompleteSnackbar, setShowScanCompleteSnackbar] = React.useState(false);
  const { data: summary, isLoading } = useComplianceSummary();
  const complianceScanMutation = useComplianceScan({
    onScanComplete: () => setShowScanCompleteSnackbar(true),
  });
  const isProActive = typeof window !== 'undefined' && window.fluxAIMediaAltCreatorAdmin?.isProActive == true;

  const totalScanned = summary?.total_scanned ?? 0;
  const coveragePercent = Number(summary?.coverage_percent) || 0;
  const highRiskCount = summary?.high_risk_count ?? 0;
  const byCategory = summary && typeof summary.by_category === 'object' ? summary.by_category : {};
  const lastScanTimestamp = summary?.last_scan_timestamp ?? null;

  const goToMediaWithFilter = (altCategory) => {
    navigate(`/media?alt_category=${encodeURIComponent(altCategory)}`);
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>
        {__('Alt Text Compliance Audit', 'flux-ai-media-alt-creator')}
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        {__('Based on WCAG 2.1 Success Criterion 1.1.1', 'flux-ai-media-alt-creator')}{' '}
        <Link href={WCAG_URL} target="_blank" rel="noopener noreferrer">
        {__('(Non-text Content)', 'flux-ai-media-alt-creator')}{' '}
        </Link>
      </Typography>

      {isLoading ? (
        <Skeleton variant="rectangular" height={200} sx={{ mb: 3 }} />
      ) : (
        <Paper sx={{ p: 3, mb: 3 }}>
          <Stack direction={{ xs: 'column', sm: 'row' }} spacing={3} alignItems="center" flexWrap="wrap">
            <Box sx={{ position: 'relative', display: 'inline-flex' }}>
              <CircularProgress
                variant="determinate"
                value={Math.min(100, coveragePercent)}
                size={80}
                thickness={4}
                color={coveragePercent >= 90 ? 'success' : coveragePercent >= 70 ? 'primary' : 'warning'}
              />
              <Box
                sx={{
                  top: 0,
                  left: 0,
                  bottom: 0,
                  right: 0,
                  position: 'absolute',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                }}
              >
                <Typography variant="h6" component="span">
                  {Number(coveragePercent).toFixed(0)}%
                </Typography>
              </Box>
            </Box>
            <Stack spacing={0.5}>
              <Typography variant="body1">
                {__('Coverage', 'flux-ai-media-alt-creator')}: <strong>{Number(coveragePercent).toFixed(1)}%</strong>
              </Typography>
              <Typography variant="body1">
                {__('Total images scanned', 'flux-ai-media-alt-creator')}: <strong>{totalScanned}</strong>
              </Typography>
              <Typography variant="body1">
                {__('High Risk', 'flux-ai-media-alt-creator')}: <strong>{highRiskCount}</strong>
              </Typography>
              <Typography variant="body2" color="text.secondary">
                {lastScanTimestamp
                  ? `${__('Last scanned:', 'flux-ai-media-alt-creator')} ${new Date(lastScanTimestamp).toLocaleString()}`
                  : __('Never scanned', 'flux-ai-media-alt-creator')}
              </Typography>
            </Stack>
            <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} alignItems={{ xs: 'stretch', sm: 'center' }}>
              <Button
                variant="contained"
                startIcon={
                  complianceScanMutation.isPending ? (
                    <CircularProgress size={20} color="inherit" aria-hidden />
                  ) : (
                    <PlayArrow aria-hidden />
                  )
                }
                onClick={() => complianceScanMutation.mutate()}
                disabled={complianceScanMutation.isPending}
              >
                {__('Run Compliance Scan', 'flux-ai-media-alt-creator')}
              </Button>
              <Button
                variant="contained"
                startIcon={<PlayArrow aria-hidden />}
                onClick={() => goToMediaWithFilter('missing')}
              >
                {__('Start Processing', 'flux-ai-media-alt-creator')}
              </Button>
            </Stack>
          </Stack>
        </Paper>
      )}

      <Typography variant="subtitle1" fontWeight={600} gutterBottom>
        {__('Risk breakdown', 'flux-ai-media-alt-creator')}
      </Typography>
      <Grid container spacing={2} sx={{ mb: 3 }}>
        {CATEGORIES.map(({ key, label, description, cta }) => (
          <Grid item xs={12} sm={6} md={4} key={key}>
            <Paper sx={{ p: 2 }}>
              <Typography variant="subtitle2" fontWeight={600}>
                {label}
              </Typography>
              <Typography variant="caption" color="text.secondary" display="block" sx={{ mt: 0.5, mb: 1 }}>
                {description}
              </Typography>
              <Typography variant="h6">
                {isLoading ? '—' : (byCategory[key] ?? 0)}
              </Typography>
              <Button size="small" onClick={() => goToMediaWithFilter(key)} sx={{ mt: 1 }}>
                {cta}
              </Button>
            </Paper>
          </Grid>
        ))}
      </Grid>

      <Snackbar
        open={showScanCompleteSnackbar}
        autoHideDuration={4000}
        onClose={() => setShowScanCompleteSnackbar(false)}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'center' }}
        sx={{ '& .MuiSnackbar-content': { alignItems: 'center' } }}
      >
        <Alert
          onClose={() => setShowScanCompleteSnackbar(false)}
          severity="success"
          icon={<CheckCircle />}
          variant="filled"
          elevation={6}
        >
          {__('Compliance scan complete.', 'flux-ai-media-alt-creator')}
        </Alert>
      </Snackbar>

      {!isProActive && (
        <Paper sx={{ p: 2, bgcolor: 'action.hover' }}>
          <Typography variant="subtitle1" fontWeight={600} gutterBottom>
            {__('Automate compliance', 'flux-ai-media-alt-creator')}
          </Typography>
          <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
            {__('Upgrade to automate large libraries and save hours.', 'flux-ai-media-alt-creator')}
          </Typography>
          <Button variant="contained" disabled>
            {__('Bulk Fix All in Background', 'flux-ai-media-alt-creator')}
          </Button>
        </Paper>
      )}
      {isProActive && (
        <Paper sx={{ p: 2, bgcolor: 'action.hover' }}>
          <Typography variant="subtitle1" fontWeight={600} gutterBottom>
            {__('Automate compliance', 'flux-ai-media-alt-creator')}
          </Typography>
          <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
            {__('Run bulk fix in the background from the Pro page.', 'flux-ai-media-alt-creator')}
          </Typography>
          <Button variant="contained" component={RouterLink} to="/pro">
            {__('Bulk Fix All in Background', 'flux-ai-media-alt-creator')}
          </Button>
        </Paper>
      )}
    </Box>
  );
};

export default CompliancePage;
