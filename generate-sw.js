#!/usr/bin/env bun

import { execSync } from 'child_process';
import config from './workbox-config.js';

// Convert the config to a JSON string
const configStr = JSON.stringify(config, (key, value) => {
  // Handle RegExp objects
  if (value instanceof RegExp) {
    return value.toString();
  }
  return value;
}, 2);

// Save to a temporary file
import { writeFileSync } from 'fs';
writeFileSync('temp-workbox-config.json', configStr);

try {
  // Run workbox with the temporary JSON config
  console.log('Generating service worker...');
  execSync('workbox generateSW temp-workbox-config.json', { stdio: 'inherit' });
  console.log('Service worker generated successfully!');
} catch (error) {
  console.error('Error generating service worker:', error);
} finally {
  // Clean up
  import { unlinkSync } from 'fs';
  try {
    unlinkSync('temp-workbox-config.json');
  } catch (e) {
    console.error('Error removing temporary file:', e);
  }
}
