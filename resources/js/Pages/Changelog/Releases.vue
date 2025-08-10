<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Icon } from '@iconify/vue'
import { computed } from 'vue'

const props = defineProps({
  releases: {
    type: Array,
    required: true,
  },
  repo: {
    type: Object,
    required: true,
  }
})

const formatDate = (d) => new Date(d).toLocaleString()

const hasCommitGroups = (release) => release.commit_groups && Object.keys(release.commit_groups).length > 0

const groupLabels = {
  feat: 'Features',
  fix: 'Bug Fixes',
  perf: 'Performance',
  refactor: 'Refactoring',
  docs: 'Documentation',
  chore: 'Chores',
  test: 'Tests',
  build: 'Build',
  ci: 'CI',
  others: 'Others',
}

const shortSha = (sha) => sha?.slice(0,7)
</script>

<template>
  <AppLayout title="Release Changelog">
    <div class="container mx-auto px-4 py-8 max-w-5xl">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Release Changelog</h1>
        <a
          class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
          :href="repo.url"
          target="_blank" rel="noopener noreferrer"
        >
          <Icon icon="mdi:github" class="h-5 w-5" />
          {{ repo.owner }}/{{ repo.name }}
        </a>
      </div>

      <div v-if="!releases?.length" class="text-muted-foreground">No releases found.</div>

      <div v-for="release in releases" :key="release.id" class="rounded-lg border bg-card text-card-foreground shadow-sm mb-6">
        <div class="p-4 sm:p-6">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs">
                  <Icon icon="lucide:tag" class="h-3.5 w-3.5" />
                  {{ release.tag_name }}
                </span>
                <span v-if="release.prerelease" class="text-xs rounded-full bg-yellow-500/10 text-yellow-700 dark:text-yellow-300 px-2 py-0.5">Pre-release</span>
                <span v-if="release.draft" class="text-xs rounded-full bg-muted px-2 py-0.5">Draft</span>
              </div>
              <h2 class="text-lg font-semibold">{{ release.name || release.tag_name }}</h2>
              <div class="text-xs text-muted-foreground">Published {{ formatDate(release.published_at) }}</div>
            </div>
            <div class="flex items-center gap-2">
              <a :href="release.html_url" target="_blank" rel="noopener noreferrer" class="text-sm underline">View on GitHub</a>
              <a v-if="release.apk_available" :href="release.apk_download_url" class="inline-flex items-center gap-1 text-sm rounded-md border px-3 py-1 hover:bg-accent hover:text-accent-foreground">
                <Icon icon="mdi:download" class="h-4 w-4" />
                APK {{ release.apk_size_human ? `(${release.apk_size_human})` : '' }}
              </a>
            </div>
          </div>

          <div v-if="release.body_html" class="prose dark:prose-invert mt-4 max-w-none" v-html="release.body_html"></div>

          <div v-if="hasCommitGroups(release)" class="mt-6 space-y-4">
            <div v-for="(items, key) in release.commit_groups" :key="key">
              <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-2">{{ groupLabels[key] || key }}</h3>
              <ul class="space-y-2">
                <li v-for="c in items" :key="c.sha" class="flex items-start gap-2 text-sm">
                  <Icon :icon="key==='fix' ? 'lucide:wrench' : key==='feat' ? 'lucide:sparkles' : 'lucide:dot'
                  " class="h-4 w-4 mt-0.5 text-muted-foreground" />
                  <div>
                    <div class="leading-relaxed">
                      {{ c.message.split("\n")[0] }}
                    </div>
                    <div class="text-xs text-muted-foreground mt-0.5 flex items-center gap-2">
                      <a :href="c.url" target="_blank" rel="noopener noreferrer" class="underline">{{ shortSha(c.sha) }}</a>
                      <span v-if="c.author">by {{ c.author }}</span>
                      <span v-if="c.date">on {{ formatDate(c.date) }}</span>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
  
</template>


