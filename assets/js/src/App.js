import React from 'react';
import { HashRouter as Router, Routes, Route, Navigate, useLocation, useNavigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ThemeProvider } from '@mui/material/styles';
import CssBaseline from '@mui/material/CssBaseline';
import { Box, Typography, Container, Tabs, Tab, Paper, Grid } from '@mui/material';
import { __ } from '@wordpress/i18n';
import theme from '../theme/index';
import OverviewPage from '../pages/OverviewPage';
import ImagesPage from '../pages/ImagesPage';
import SettingsPage from '../pages/SettingsPage';

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
 * Error boundary component
 */
class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true };
  }

  componentDidCatch(error, errorInfo) {
    console.error('Error caught by boundary:', error, errorInfo);
  }

  render() {
    if (this.state.hasError) {
      return (
        <Box sx={{ p: 3 }}>
          <Typography variant="h6" color="error">
            {__('Something went wrong. Please refresh the page.', 'flux-ai-media-alt-creator')}
          </Typography>
        </Box>
      );
    }

    return this.props.children;
  }
}

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
      case '/images':
        return 1;
      case '/settings':
        return 2;
      default:
        return 0;
    }
  };

  const handleTabChange = (event, newValue) => {
    const paths = ['/overview', '/images', '/settings'];
    navigate(paths[newValue]);
  };

  return (
    <Box sx={{ borderBottom: 1, borderColor: 'divider', mb: 3 }}>
      <Grid container alignItems="center" sx={{ mb: 2 }}>
        <Grid item sx={{ display: 'flex', alignItems: 'center' }}>
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
        <Tab label={__('Images', 'flux-ai-media-alt-creator')} />
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
          <CssBaseline />
          <Router>
            <Container maxWidth="xl" sx={{ py: 4 }}>
              <Paper elevation={1} sx={{ p: 3 }}>
                <Navigation />
                <Routes>
                  <Route path="/overview" element={<OverviewPage />} />
                  <Route path="/images" element={<ImagesPage />} />
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

