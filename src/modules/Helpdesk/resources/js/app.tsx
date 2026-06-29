import React from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClientProvider, QueryClient } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';

// Initialize React Query
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5 minutes
      gcTime: 1000 * 60 * 10, // 10 minutes (formerly cacheTime)
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});

/**
 * Mount React islands on the page
 *
 * This function looks for HTML elements with data-react-component attribute
 * and mounts the corresponding React component inside them.
 *
 * Example Blade:
 * <div id="conversations-list" data-react-component="ConversationsList" data-props='{"initialData":[]}'>
 * </div>
 */
const mountReactIslands = async () => {
  // Dynamically import all React components
  const components = import.meta.glob('./components/**/*.tsx', { eager: true });

  // Find all elements that need React components mounted
  document.querySelectorAll('[data-react-component]').forEach((element) => {
    const componentName = element.getAttribute('data-react-component');
    const propsJson = element.getAttribute('data-props') || '{}';

    if (!componentName) return;

    // Find matching component
    const componentPath = Object.keys(components).find((path) =>
      path.includes(`${componentName}.tsx`)
    );

    if (!componentPath) {
      console.warn(`Component not found: ${componentName}`);
      return;
    }

    try {
      // Get the component from the import
      const module = components[componentPath] as any;
      const Component = module.default || module[componentName];

      if (!Component) {
        console.warn(`No default export found in ${componentPath}`);
        return;
      }

      // Parse props from data attribute
      const props = JSON.parse(propsJson);

      // Mount React component
      const root = createRoot(element);
      root.render(
        <QueryClientProvider client={queryClient}>
          <Component {...props} />
          {process.env.NODE_ENV === 'development' && <ReactQueryDevtools />}
        </QueryClientProvider>
      );
    } catch (error) {
      console.error(`Error mounting component ${componentName}:`, error);
    }
  });
};

// Mount islands when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mountReactIslands);
} else {
  mountReactIslands();
}

// Support Livewire morphs (re-initialize islands after Livewire updates)
if (window.Livewire) {
  window.Livewire.hook('morph.updated', () => {
    setTimeout(mountReactIslands, 100);
  });
}
