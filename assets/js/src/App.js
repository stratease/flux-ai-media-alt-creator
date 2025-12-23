import React from 'react';
import { HashRouter as Router, Routes, Route, Navigate, useLocation, useNavigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ThemeProvider } from '@mui/material/styles';
import { Global, css } from '@emotion/react';
import { Box, Typography, Container, Tabs, Tab, Paper, Grid } from '@mui/material';
import { __ } from '@wordpress/i18n';
import { ErrorBoundary, FluxAIMediaAltIcon } from '@flux-ai-media-alt-creator/components';
import OverviewPage from '@flux-ai-media-alt-creator/pages/OverviewPage';
import MediaPage from '@flux-ai-media-alt-creator/pages/MediaPage';
import SettingsPage from '@flux-ai-media-alt-creator/pages/SettingsPage';
import theme from '@flux-ai-media-alt-creator/theme';

// Create a client
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});

/**
 * Navigation component with tabs using React Router
 */
const Navigation = () => {
  const location = useLocation();
  const navigate = useNavigate();

  const getTabValue = (pathname) => {
    switch (pathname) {
      case '/overview':
        return 0;
      case '/media':
        return 1;
      case '/settings':
        return 2;
      default:
        return 0;
    }
  };

  const handleTabChange = (event, newValue) => {
    const paths = ['/overview', '/media', '/settings'];
    navigate(paths[newValue]);
  };

  return (
    <Box sx={{ borderBottom: 1, borderColor: 'divider', mb: 3 }}>
      <Grid container alignItems="center" sx={{ mb: 2 }}>
        <Grid item sx={{ display: 'flex', alignItems: 'center' }}>
          <FluxAIMediaAltIcon size={40} sx={{ mr: 2 }} />
          <Typography variant="h4" component="h1" sx={{ m: 0, lineHeight: 1 }}>
            {__('Flux AI Media Alt Creator', 'flux-ai-media-alt-creator')}
          </Typography>
        </Grid>
      </Grid>
      <Tabs
        value={getTabValue(location.pathname)}
        onChange={handleTabChange}
        aria-label={__('Flux AI Media Alt Creator navigation tabs', 'flux-ai-media-alt-creator')}
        textColor="primary"
        indicatorColor="primary"
      >
        <Tab label={__('Overview', 'flux-ai-media-alt-creator')} />
        <Tab label={__('Media', 'flux-ai-media-alt-creator')} />
        <Tab label={__('Settings', 'flux-ai-media-alt-creator')} />
      </Tabs>
    </Box>
  );
};

/**
 * Main App component with React Router
 */
const App = () => {
  // Handle initial route from WordPress admin menu
  React.useEffect(() => {
    const container = document.getElementById('flux-ai-media-alt-creator-app');
    const initialHash = container?.dataset.initialHash;
    
    if (initialHash) {
      const hash = initialHash.startsWith('#') ? initialHash.slice(1) : initialHash;
      // Set the initial hash if it's different from current
      if (window.location.hash !== `#${hash}`) {
        window.location.hash = hash;
      }
    }
  }, []);

  return (
    <ErrorBoundary>
      <QueryClientProvider client={queryClient}>
        <ThemeProvider theme={theme}>
          <Global
            styles={css`
              .MuiCheckbox-root input[type="checkbox"] {
                opacity: 0 !important;
                position: absolute !important;
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                cursor: pointer !important;
                z-index: 1 !important;
              }
            `}
          />
          <Router>
            <Container maxWidth="xl" sx={{ py: 4 }}>
              <Paper elevation={1} sx={{ p: 3 }}>
                <Navigation />
                <Routes>
                  <Route path="/overview" element={<OverviewPage />} />
                  <Route path="/media" element={<MediaPage />} />
                  <Route path="/settings" element={<SettingsPage />} />
                  <Route path="/" element={<Navigate to="/overview" replace />} />
                </Routes>
              </Paper>
            </Container>
          </Router>
        </ThemeProvider>
      </QueryClientProvider>
    </ErrorBoundary>
  );
};

export default App;

