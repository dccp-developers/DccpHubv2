<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Badge } from '@/Components/shadcn/ui/badge';
import { Separator } from '@/Components/shadcn/ui/separator';
import { Button } from '@/Components/shadcn/ui/button';

const props = defineProps({
    releases: {
        type: Array,
        required: true,
    },
});

const formatDate = (dateString) => {
    if (!dateString) return null;
    try {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    } catch {
        return null;
    }
};

const getReleaseType = (release) => {
    const content = release.content?.toLowerCase() || '';
    const title = release.title?.toLowerCase() || '';

    if (content.includes('major') || content.includes('breaking') || title.includes('major')) {
        return { label: 'Major', variant: 'destructive' };
    } else if (content.includes('feature') || content.includes('new') || title.includes('feature')) {
        return { label: 'Feature', variant: 'default' };
    } else if (content.includes('fix') || content.includes('bug') || title.includes('fix')) {
        return { label: 'Fix', variant: 'secondary' };
    }
    return { label: 'Release', variant: 'outline' };
};
</script>

<template>
    <AppLayout title="Changelog">
        <div class="container mx-auto max-w-5xl px-6 py-12">
            <!-- Elegant Header -->
            <header class="text-center mb-16 border-b border-border pb-12">
                <div class="space-y-4">
                    <h1 class="text-4xl font-light tracking-tight text-foreground">
                        Changelog
                    </h1>
                    <p class="text-lg text-muted-foreground max-w-2xl mx-auto leading-relaxed">
                        A comprehensive history of updates, improvements, and new features
                        for the DCCPHub Mobile Application
                    </p>
                    <div v-if="releases.length" class="flex items-center justify-center gap-8 pt-4 text-sm text-muted-foreground">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 border border-border"></div>
                            <span>{{ releases.length }} releases</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 border border-border"></div>
                            <span>Latest: v{{ releases[0]?.version }}</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- No Releases State -->
            <div v-if="!releases.length" class="text-center py-24">
                <div class="space-y-4">
                    <div class="w-16 h-16 border border-border mx-auto"></div>
                    <h3 class="text-xl font-medium text-foreground">No releases available</h3>
                    <p class="text-muted-foreground">
                        Release notes will appear here once they are published.
                    </p>
                </div>
            </div>

            <!-- Releases Timeline -->
            <div v-else class="space-y-0">
                <article
                    v-for="(release, index) in releases"
                    :key="release.version"
                    class="relative"
                >
                    <!-- Timeline connector -->
                    <div
                        v-if="index < releases.length - 1"
                        class="absolute left-8 top-20 w-px h-full border-l border-border"
                    />

                    <!-- Release Container -->
                    <div class="relative flex gap-8 pb-16">
                        <!-- Timeline Marker -->
                        <div class="flex-shrink-0 relative">
                            <div class="w-4 h-4 border-2 border-border bg-background relative z-10 timeline-marker">
                                <div
                                    v-if="index === 0"
                                    class="absolute inset-1 bg-foreground"
                                />
                            </div>
                        </div>

                        <!-- Release Content -->
                        <div class="flex-1 min-w-0 -mt-2">
                            <!-- Release Header -->
                            <header class="mb-6">
                                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-4">
                                    <div class="space-y-2">
                                        <h2 class="text-2xl font-medium text-foreground leading-tight">
                                            {{ release.title }}
                                        </h2>
                                        <div class="flex items-center gap-4">
                                            <Badge
                                                :variant="getReleaseType(release).variant"
                                                class="font-normal changelog-badge"
                                            >
                                                {{ getReleaseType(release).label }}
                                            </Badge>
                                            <span class="text-sm text-muted-foreground font-mono">
                                                v{{ release.version }}
                                            </span>
                                        </div>
                                    </div>
                                    <div v-if="release.date" class="text-sm text-muted-foreground lg:text-right">
                                        <time :datetime="release.date">
                                            {{ formatDate(release.date) }}
                                        </time>
                                    </div>
                                </div>
                                <Separator class="my-6" />
                            </header>

                            <!-- Release Content -->
                            <div
                                v-html="release.content"
                                class="changelog-content prose prose-neutral dark:prose-invert max-w-none"
                            />
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Elegant changelog styling with shadcn design system */
.changelog-content {
    line-height: 1.7;
    color: hsl(var(--foreground));
    font-size: 0.95rem;
}

/* Hide the main title since we handle it in the component */
.changelog-content h1:first-child {
    display: none;
}

/* Elegant heading hierarchy */
.changelog-content h1 {
    font-size: 1.75rem;
    font-weight: 500;
    margin: 3rem 0 1.5rem 0;
    color: hsl(var(--foreground));
    border-bottom: 1px solid hsl(var(--border));
    padding-bottom: 1rem;
    letter-spacing: -0.025em;
}

.changelog-content h2 {
    font-size: 1.375rem;
    font-weight: 500;
    margin: 2.5rem 0 1rem 0;
    color: hsl(var(--foreground));
    letter-spacing: -0.025em;
}

.changelog-content h3 {
    font-size: 1.125rem;
    font-weight: 500;
    margin: 2rem 0 0.75rem 0;
    color: hsl(var(--foreground));
}

.changelog-content h4 {
    font-size: 0.875rem;
    font-weight: 500;
    margin: 1.5rem 0 0.75rem 0;
    color: hsl(var(--muted-foreground));
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

/* Refined paragraph styling */
.changelog-content p {
    margin-bottom: 1.25rem;
    color: hsl(var(--foreground));
    line-height: 1.7;
}

/* Elegant list styling */
.changelog-content ul,
.changelog-content ol {
    margin-bottom: 1.5rem;
    padding-left: 2rem;
}

.changelog-content li {
    margin-bottom: 0.5rem;
    color: hsl(var(--foreground));
    line-height: 1.6;
}

.changelog-content ul li {
    position: relative;
}

.changelog-content ul li::before {
    content: '';
    position: absolute;
    left: -1.5rem;
    top: 0.7rem;
    width: 1px;
    height: 1px;
    border: 2px solid hsl(var(--foreground));
    background: hsl(var(--foreground));
}

/* Refined emphasis */
.changelog-content strong {
    font-weight: 600;
    color: hsl(var(--foreground));
}

/* Clean code styling without rounded borders */
.changelog-content code {
    background-color: hsl(var(--muted));
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    font-family: ui-monospace, 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
    color: hsl(var(--foreground));
    border: 1px solid hsl(var(--border));
}

.changelog-content pre {
    background-color: hsl(var(--muted));
    padding: 1.5rem;
    overflow-x: auto;
    margin: 1.5rem 0;
    border: 1px solid hsl(var(--border));
    font-size: 0.875rem;
}

.changelog-content pre code {
    background-color: transparent;
    padding: 0;
    border: none;
}

/* Elegant blockquote */
.changelog-content blockquote {
    border-left: 2px solid hsl(var(--border));
    padding-left: 1.5rem;
    margin: 2rem 0;
    font-style: italic;
    color: hsl(var(--muted-foreground));
    position: relative;
}

.changelog-content blockquote::before {
    content: '';
    position: absolute;
    left: -2px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: hsl(var(--primary));
}

/* Clean horizontal rules */
.changelog-content hr {
    margin: 3rem 0;
    border: none;
    border-top: 1px solid hsl(var(--border));
}

/* Elegant table styling */
.changelog-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 2rem 0;
    border: 1px solid hsl(var(--border));
}

.changelog-content th,
.changelog-content td {
    padding: 1rem;
    text-align: left;
    border-right: 1px solid hsl(var(--border));
    border-bottom: 1px solid hsl(var(--border));
}

.changelog-content th:last-child,
.changelog-content td:last-child {
    border-right: none;
}

.changelog-content tbody tr:last-child td {
    border-bottom: none;
}

.changelog-content th {
    background-color: hsl(var(--muted));
    font-weight: 500;
    color: hsl(var(--foreground));
    font-size: 0.875rem;
    letter-spacing: 0.025em;
}

.changelog-content tbody tr:nth-child(even) {
    background-color: hsl(var(--muted) / 0.3);
}

/* Refined links */
.changelog-content a {
    color: hsl(var(--primary));
    text-decoration: none;
    border-bottom: 1px solid hsl(var(--primary) / 0.3);
    transition: border-color 0.2s ease;
}

.changelog-content a:hover {
    border-bottom-color: hsl(var(--primary));
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .changelog-content {
        font-size: 0.9rem;
    }

    .changelog-content h1 {
        font-size: 1.5rem;
        margin: 2rem 0 1rem 0;
    }

    .changelog-content h2 {
        font-size: 1.25rem;
        margin: 2rem 0 0.75rem 0;
    }

    .changelog-content h3 {
        font-size: 1.125rem;
        margin: 1.5rem 0 0.5rem 0;
    }

    .changelog-content pre {
        padding: 1rem;
        font-size: 0.8rem;
    }

    .changelog-content table {
        font-size: 0.875rem;
    }

    .changelog-content th,
    .changelog-content td {
        padding: 0.75rem 0.5rem;
    }

    .changelog-content ul,
    .changelog-content ol {
        padding-left: 1.5rem;
    }
}

/* Custom badge styling without rounded borders */
.changelog-badge {
    border-radius: 0 !important;
    border: 1px solid hsl(var(--border));
    font-weight: 400;
    letter-spacing: 0.025em;
    text-transform: uppercase;
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
}

/* Timeline marker styling */
.timeline-marker {
    transition: border-color 0.2s ease;
}

/* Hover effects for interactive elements */
article:hover .timeline-marker {
    border-color: hsl(var(--primary));
}

/* Separator styling */
:deep(.separator) {
    border-radius: 0;
}
</style>