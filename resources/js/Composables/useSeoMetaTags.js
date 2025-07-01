import { useSeoMeta } from '@unhead/vue'

// Default SEO meta tags for DCCPhub
const defaultSeoMeta = {
  title: 'Home',
  titleTemplate: '%s | DCCPhub - Data Center College Of The Philippines Portal',
  description: 'DCCPhub is the official school portal for Data Center College Of The Philippines. Access student resources, announcements, grades, schedules, and more in one modern platform.',
  keywords: 'DCCPhub, Data Center College Of The Philippines, DCCP, school portal, student portal, grades, schedules, announcements, education, Philippines',
  robots: 'index, follow',
  themeColor: '#003366',

  // Open Graph
  ogTitle: '%s | DCCPhub - Data Center College Of The Philippines Portal',
  ogDescription: 'DCCPhub is the official portal for students and staff of Data Center College Of The Philippines. Stay updated with announcements, grades, and resources.',
  ogUrl: 'https://portal.dccp.edu.ph',
  ogType: 'website',
  ogImage: 'https://portal.dccp.edu.ph/images/og.png',
  ogSiteName: 'DCCPhub',
  ogLocale: 'en_PH',

  // Twitter
  twitterTitle: '%s | DCCPhub - Data Center College Of The Philippines Portal',
  twitterDescription: 'DCCPhub is the official portal for students and staff of Data Center College Of The Philippines. Stay updated with announcements, grades, and resources.',
  twitterCard: 'summary_large_image',
  twitterImage: 'https://portal.dccp.edu.ph/images/og.png',
  twitterSite: '@dccphub',  
}

/**
 * Composable for managing SEO meta tags
 * @param {object|null} seoMeta - Custom SEO meta tags to apply
 * @param {object} options - Configuration options
 * @param {boolean} options.merge - When true, merges custom meta tags with defaults.
 *                                 When false, only uses custom meta tags.
 *                                 Useful for pages that need completely custom SEO
 *                                 without inheriting defaults.
 * @returns {void}
 */
export function useSeoMetaTags(seoMeta, options = { merge: true }) {
  if (!seoMeta)
    return useSeoMeta(defaultSeoMeta)

  return useSeoMeta(
    options.merge
      ? { ...defaultSeoMeta, ...seoMeta }
      : seoMeta,
  )
}
