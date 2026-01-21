import React from 'react';
import { createRoot } from 'react-dom/client';
import ReactDOM from 'react-dom';
import * as ReactJsxRuntime from 'react/jsx-runtime';
import * as EmotionReact from '@emotion/react';
import * as EmotionStyled from '@emotion/styled';
import * as ReactQuery from '@tanstack/react-query';
import App from '../App';

// CRITICAL: Expose React, ReactDOM, React JSX runtime, and dependencies globally IMMEDIATELY
// This must happen synchronously at module load time, before any other code executes
// Pro plugin's webpack externals depend on these being available
if (typeof window !== 'undefined') {
  // Expose React
  window.React = React;
  
  // Expose ReactDOM client API for React 18+
  window.ReactDOM = { ...ReactDOM, createRoot };
  
  // Expose React JSX runtime for automatic JSX transform (Babel runtime: 'automatic')
  // Pro plugin's webpack externalizes 'react/jsx-runtime' to 'ReactJsxRuntime'
  // This object must have jsx, jsxs, Fragment exports
  window.ReactJsxRuntime = ReactJsxRuntime;
  
  // Expose Emotion for styled components
  window.EmotionReact = EmotionReact;
  window.EmotionStyled = EmotionStyled;
  
  // Expose React Query so Pro plugin can use the same instance and see QueryClientProvider context
  // Pro plugin's webpack externalizes '@tanstack/react-query' to 'ReactQuery'
  window.ReactQuery = ReactQuery;

  // Create FLUX_EXTENSIONS registry for extension system
  // This allows Pro plugin and other extensions to register components at runtime
  if (!window.FLUX_EXTENSIONS) {
    window.FLUX_EXTENSIONS = {
      version: '1.0',
      _registry: {},
      
      /**
       * Register an extension in a slot
       * @param {string} slot - Slot identifier (e.g., 'flux.admin.tabs' or 'flux.admin.settings.sidebar')
       * @param {Object} extension - Extension object with id, priority, render/component, optional condition
       */
      register(slot, extension) {
        if (!this._registry[slot]) {
          this._registry[slot] = [];
        }
        this._registry[slot].push({
          ...extension,
          slot,
          registeredAt: Date.now(),
        });
        // Sort by priority (higher priority first)
        this._registry[slot].sort((a, b) => (b.priority || 10) - (a.priority || 10));
        
        // Dispatch custom event to notify App of new extension registration
        if (typeof window !== 'undefined' && window.dispatchEvent) {
          const event = new CustomEvent('flux-extensions-registered', {
            detail: { slot, extensionId: extension.id },
          });
          window.dispatchEvent(event);
        }
      },
      
      /**
       * Get all extensions for a slot
       * @param {string} slot - Slot identifier
       * @param {Object} context - Optional context for conditional extensions
       * @returns {Array} Array of extensions
       */
      get(slot, context = {}) {
        const extensions = this._registry[slot] || [];
        // Filter by condition if provided
        return extensions.filter(ext => {
          if (ext.condition && typeof ext.condition === 'function') {
            return ext.condition(context);
          }
          return true;
        });
      },
      
      /**
       * Unregister an extension
       * @param {string} slot - Slot identifier
       * @param {string} id - Extension ID
       */
      unregister(slot, id) {
        if (this._registry[slot]) {
          this._registry[slot] = this._registry[slot].filter(ext => ext.id !== id);
        }
      },
      
      /**
       * Get all registered slots
       * @returns {Array} Array of slot identifiers
       */
      getSlots() {
        return Object.keys(this._registry);
      },
    };
  }
}

const container = document.getElementById('flux-ai-media-alt-creator-app');
if (container) {
  const root = createRoot(container);
  root.render(<App />);
}

