import React from 'react';
import { HashRouter as Router, Routes, Route, Navigate, useLocation, useNavigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Box, Tabs, Tab } from '@mui/material';
import { __ } from '@wordpress/i18n';
import { ErrorBoundary } from '@flux-ai-media-alt-creator/components';
import { FluxAppProvider, PageLayout } from '@flux-plugins-common/components';
import OverviewPage from '@flux-ai-media-alt-creator/pages/OverviewPage';
import MediaPage from '@flux-ai-media-alt-creator/pages/MediaPage';
import SettingsPage from '@flux-ai-media-alt-creator/pages/SettingsPage';

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
 * Get all tabs for navigation (merges PHP-defined tabs with extension registry tabs)
 */
const getAllTabs = () => {
  // Get registered tabs from PHP filter (for server-side routing/nav labels).
  const phpTabs = window.fluxAIMediaAltCreatorAdmin?.tabs || [];
  
  // Get registered tab extensions from FLUX_EXTENSIONS registry.
  const extensionTabs = window.FLUX_EXTENSIONS?.get('flux.admin.tabs', {}) || [];
  
  // Merge PHP tabs with extension tabs for navigation display.
  const allTabs = [
    { slug: 'overview', label: __('Overview', 'flux-ai-media-alt-creator'), path: '/overview' },
    { slug: 'media', label: __('Media', 'flux-ai-media-alt-creator'), path: '/media' },
    { slug: 'settings', label: __('Settings', 'flux-ai-media-alt-creator'), path: '/settings' },
    ...phpTabs.map(tab => ({
      slug: tab.slug,
      label: tab.label,
      path: `/${tab.slug}`,
    })),
    ...extensionTabs.map(tab => ({
      slug: tab.slug || tab.id,
      label: tab.label || tab.id,
      path: `/${tab.slug || tab.id}`,
    })),
  ];
  
  // Remove duplicates (keep first occurrence).
  return allTabs.filter((tab, index, self) => 
    index === self.findIndex(t => t.slug === tab.slug)
  );
};

/**
 * Navigation component with tabs using React Router
 */
const Navigation = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const allTabs = getAllTabs();

  const getTabValue = (pathname) => {
    const index = allTabs.findIndex(tab => tab.path === pathname);
    return index >= 0 ? index : 0;
  };

  const handleTabChange = (event, newValue) => {
    if (allTabs[newValue]) {
      navigate(allTabs[newValue].path);
    }
  };

  return (
    <Box sx={{ borderBottom: 1, borderColor: 'divider', mb: 3 }}>
      <Tabs
        value={getTabValue(location.pathname)}
        onChange={handleTabChange}
        aria-label={__('Flux AI Media Alt Creator navigation tabs', 'flux-ai-media-alt-creator')}
        textColor="primary"
        indicatorColor="primary"
      >
        {allTabs.map((tab) => (
          <Tab key={tab.slug} label={tab.label} />
        ))}
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

  // Track extension registrations to force re-render when new extensions are added
  const [extensionVersion, setExtensionVersion] = React.useState(0);
  
  React.useEffect(() => {
    const handleExtensionRegistered = (event) => {
      // Force re-render by incrementing version counter
      setExtensionVersion(prev => prev + 1);
    };
    
    window.addEventListener('flux-extensions-registered', handleExtensionRegistered);
    return () => {
      window.removeEventListener('flux-extensions-registered', handleExtensionRegistered);
    };
  }, [extensionVersion]);

  // Get all tabs for routing (same logic as Navigation component).
  // Re-compute when extensionVersion changes to pick up new extensions
  const allTabs = React.useMemo(() => {
    return getAllTabs();
  }, [extensionVersion]);

  // Render registered tab component using extension registry.
  // This function should ONLY be called for extension tabs (not default tabs).
  const renderRegisteredTab = React.useCallback((tab) => {
    // Get extensions for the tabs slot (call this fresh each render to get latest registrations)
    const extensions = window.FLUX_EXTENSIONS?.get('flux.admin.tabs', {}) || [];
    
    // Debug logging
    if (process.env.NODE_ENV === 'development') {
      console.log(`[Flux Extensions] Looking for tab: ${tab.slug}`, {
        availableExtensions: extensions.map(e => ({ id: e.id, slug: e.slug })),
        registry: window.FLUX_EXTENSIONS?.getSlots(),
      });
    }
    
    // Find extension that matches this tab slug
    const tabExtension = extensions.find(ext => {
      const extSlug = ext.slug || ext.id;
      return extSlug === tab.slug;
    });
    
    if (!tabExtension) {
      // Fallback: check if tab has a component name (legacy PHP registration)
      if (tab.component && window[tab.component]) {
        const Component = window[tab.component];
        if (typeof Component === 'function' || (typeof Component === 'object' && Component !== null)) {
          return (
            <ErrorBoundary key={tab.slug}>
              <Component />
            </ErrorBoundary>
          );
        }
      }
      
      // Return a valid React element (not null) for missing components
      return (
        <div key={tab.slug}>
          <p>{__('Component not found', 'flux-ai-media-alt-creator')}: {tab.slug}</p>
          {process.env.NODE_ENV === 'development' && (
            <pre>{JSON.stringify({ tab, extensions: extensions.map(e => ({ id: e.id, slug: e.slug })) }, null, 2)}</pre>
          )}
        </div>
      );
    }

    // Use extension's render function if provided
    if (tabExtension.render && typeof tabExtension.render === 'function') {
      try {
        const rendered = tabExtension.render({ tab, ...(tabExtension.props || {}) });
        // Ensure rendered value is a valid React element
        if (!React.isValidElement(rendered)) {
          console.error(`Extension ${tabExtension.id} render function did not return a valid React element`);
          return (
            <div key={tabExtension.id || tab.slug}>
              {__('Invalid render output', 'flux-ai-media-alt-creator')}: {tab.slug}
            </div>
          );
        }
        return (
          <ErrorBoundary key={tabExtension.id || tab.slug}>
            {rendered}
          </ErrorBoundary>
        );
      } catch (error) {
        console.error(`Error rendering extension ${tabExtension.id}:`, error);
        return (
          <div key={tabExtension.id || tab.slug}>
            {__('Error rendering component', 'flux-ai-media-alt-creator')}: {error.message}
          </div>
        );
      }
    }

    // Use extension's component if provided
    if (tabExtension.component) {
      try {
        const Component = tabExtension.component;
        
        // Ensure Component is actually a React component
        if (typeof Component !== 'function' && (typeof Component !== 'object' || Component === null)) {
          console.error(`Extension ${tabExtension.id} component is not a valid React component:`, typeof Component, Component);
          return (
            <div key={tabExtension.id || tab.slug}>
              {__('Invalid component type', 'flux-ai-media-alt-creator')}: {tab.slug} (type: {typeof Component})
            </div>
          );
        }
        
        // Validate that Component can be rendered as a React element
        // This ensures React recognizes it as a valid component
        let componentElement;
        try {
          componentElement = React.createElement(Component, tabExtension.props || {});
          if (!React.isValidElement(componentElement)) {
            throw new Error('Component did not create a valid React element');
          }
        } catch (createError) {
          console.error(`Error creating element from component ${tabExtension.id}:`, createError);
          return (
            <div key={tabExtension.id || tab.slug}>
              {__('Error creating component element', 'flux-ai-media-alt-creator')}: {createError.message}
            </div>
          );
        }
        
        // Render the component - ensure it's wrapped properly
        return (
          <ErrorBoundary key={tabExtension.id || tab.slug}>
            {componentElement}
          </ErrorBoundary>
        );
      } catch (error) {
        console.error(`Error rendering component for ${tabExtension.id}:`, error);
        return (
          <div key={tabExtension.id || tab.slug}>
            {__('Error rendering component', 'flux-ai-media-alt-creator')}: {error.message}
            {process.env.NODE_ENV === 'development' && (
              <pre>{error.stack}</pre>
            )}
          </div>
        );
      }
    }
    
    // Return a valid React element (not null) when no render/component is provided
    return (
      <div key={tab.slug}>
        {__('No render function or component provided', 'flux-ai-media-alt-creator')}: {tab.slug}
      </div>
    );
  }, []);

  return (
    <ErrorBoundary>
      <QueryClientProvider client={queryClient}>
        <FluxAppProvider>
          <Router>
            <PageLayout title={__('Flux AI Media Alt Creator', 'flux-ai-media-alt-creator')} maxWidth="xl">
              <Navigation />
              <Routes>
                <Route path="/overview" element={<OverviewPage />} />
                <Route path="/media" element={<MediaPage />} />
                <Route path="/settings" element={<SettingsPage />} />
                {/* Only create routes for extension tabs (not default tabs) */}
                {allTabs
                  .filter(tab => !['overview', 'media', 'settings'].includes(tab.slug))
                  .map((tab) => {
                    const element = renderRegisteredTab(tab);
                    // Only create route if we have a valid element (should always be true, but safety check)
                    if (element && React.isValidElement(element)) {
                      return (
                        <Route
                          key={tab.slug}
                          path={`/${tab.slug}`}
                          element={element}
                        />
                      );
                    }
                    return null;
                  })
                  .filter(Boolean)}
                <Route path="/" element={<Navigate to="/overview" replace />} />
              </Routes>
            </PageLayout>
          </Router>
        </FluxAppProvider>
      </QueryClientProvider>
    </ErrorBoundary>
  );
};

export default App;

