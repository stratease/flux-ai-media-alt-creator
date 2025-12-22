import React from 'react';
import { createRoot } from 'react-dom/client';
import App from '../App';

const container = document.getElementById('flux-ai-media-alt-creator-app');
if (container) {
  const root = createRoot(container);
  root.render(<App />);
}

