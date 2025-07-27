<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-4xl max-h-[90vh] overflow-hidden">
      <DialogHeader>
        <DialogTitle class="flex items-center space-x-2">
          <BookOpenIcon class="w-6 h-6 text-primary" />
          <span>Faculty Teaching Guide</span>
        </DialogTitle>
        <DialogDescription>
          Comprehensive guide to help you navigate the faculty portal and enhance your teaching experience
        </DialogDescription>
      </DialogHeader>

      <div class="flex flex-col h-[70vh]">
        <!-- Navigation Tabs -->
        <Tabs v-model="activeTab" class="w-full">
          <TabsList class="grid w-full grid-cols-4">
            <TabsTrigger value="getting-started">Getting Started</TabsTrigger>
            <TabsTrigger value="features">Features</TabsTrigger>
            <TabsTrigger value="tips">Tips & Tricks</TabsTrigger>
            <TabsTrigger value="support">Support</TabsTrigger>
          </TabsList>

          <!-- Getting Started Tab -->
          <TabsContent value="getting-started" class="mt-4 overflow-y-auto">
            <div class="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle class="flex items-center space-x-2">
                    <RocketLaunchIcon class="w-5 h-5 text-green-600" />
                    <span>Welcome to Faculty Portal</span>
                  </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                  <p class="text-muted-foreground">
                    The Faculty Portal is designed to streamline your teaching workflow and provide easy access to all your academic tools.
                  </p>
                  
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 border rounded-lg">
                      <h4 class="font-semibold flex items-center space-x-2 mb-2">
                        <HomeIcon class="w-4 h-4 text-blue-600" />
                        <span>Dashboard Overview</span>
                      </h4>
                      <p class="text-sm text-muted-foreground">
                        Your dashboard provides a quick overview of today's schedule, classes, and important metrics.
                      </p>
                    </div>
                    
                    <div class="p-4 border rounded-lg">
                      <h4 class="font-semibold flex items-center space-x-2 mb-2">
                        <AcademicCapIcon class="w-4 h-4 text-purple-600" />
                        <span>Class Management</span>
                      </h4>
                      <p class="text-sm text-muted-foreground">
                        Manage your classes, view student lists, and track academic progress all in one place.
                      </p>
                    </div>
                    
                    <div class="p-4 border rounded-lg">
                      <h4 class="font-semibold flex items-center space-x-2 mb-2">
                        <CalendarIcon class="w-4 h-4 text-orange-600" />
                        <span>Schedule Management</span>
                      </h4>
                      <p class="text-sm text-muted-foreground">
                        View your weekly schedule, manage office hours, and track upcoming deadlines.
                      </p>
                    </div>
                    
                    <div class="p-4 border rounded-lg">
                      <h4 class="font-semibold flex items-center space-x-2 mb-2">
                        <UsersIcon class="w-4 h-4 text-green-600" />
                        <span>Student Interaction</span>
                      </h4>
                      <p class="text-sm text-muted-foreground">
                        Access student information, track attendance, and manage grades efficiently.
                      </p>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle class="flex items-center space-x-2">
                    <PlayCircleIcon class="w-5 h-5 text-blue-600" />
                    <span>Quick Start Steps</span>
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div class="space-y-3">
                    <div class="flex items-start space-x-3">
                      <div class="flex-shrink-0 w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-semibold">1</div>
                      <div>
                        <h4 class="font-medium">Review Your Dashboard</h4>
                        <p class="text-sm text-muted-foreground">Start by familiarizing yourself with the dashboard layout and available information.</p>
                      </div>
                    </div>
                    
                    <div class="flex items-start space-x-3">
                      <div class="flex-shrink-0 w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-semibold">2</div>
                      <div>
                        <h4 class="font-medium">Check Your Classes</h4>
                        <p class="text-sm text-muted-foreground">Navigate to "My Classes" to see all your assigned courses and student enrollments.</p>
                      </div>
                    </div>
                    
                    <div class="flex items-start space-x-3">
                      <div class="flex-shrink-0 w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-semibold">3</div>
                      <div>
                        <h4 class="font-medium">Explore Your Schedule</h4>
                        <p class="text-sm text-muted-foreground">View your weekly schedule and plan your teaching activities accordingly.</p>
                      </div>
                    </div>
                    
                    <div class="flex items-start space-x-3">
                      <div class="flex-shrink-0 w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-semibold">4</div>
                      <div>
                        <h4 class="font-medium">Customize Your Settings</h4>
                        <p class="text-sm text-muted-foreground">Adjust semester and school year settings to match your current teaching period.</p>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          <!-- Features Tab -->
          <TabsContent value="features" class="mt-4 overflow-y-auto">
            <div class="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle class="flex items-center space-x-2">
                    <SparklesIcon class="w-5 h-5 text-purple-600" />
                    <span>Key Features</span>
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                      <h4 class="font-semibold text-lg">Class Management</h4>
                      <ul class="space-y-2 text-sm">
                        <li class="flex items-center space-x-2">
                          <CheckIcon class="w-4 h-4 text-green-600" />
                          <span>View all assigned classes</span>
                        </li>
                        <li class="flex items-center space-x-2">
                          <CheckIcon class="w-4 h-4 text-green-600" />
                          <span>Access student enrollment lists</span>
                        </li>
                        <li class="flex items-center space-x-2">
                          <CheckIcon class="w-4 h-4 text-green-600" />
                          <span>Track class schedules and rooms</span>
                        </li>
                        <li class="flex items-center space-x-2">
                          <CheckIcon class="w-4 h-4 text-green-600" />
                          <span>Monitor student attendance</span>
                        </li>
                      </ul>
                    </div>
                    
                    <div class="space-y-4">
                      <h4 class="font-semibold text-lg">Student Management</h4>
                      <ul class="space-y-2 text-sm">
                        <li class="flex items-center space-x-2">
                          <CheckIcon class="w-4 h-4 text-green-600" />
                          <span>View student profiles and details</span>
                        </li>
                        <li class="flex items-center space-x-2">
                          <CheckIcon class="w-4 h-4 text-green-600" />
                          <span>Track academic performance</span>
                        </li>
                        <li class="flex items-center space-x-2">
                          <CheckIcon class="w-4 h-4 text-green-600" />
                          <span>Filter students by various criteria</span>
                        </li>
                        <li class="flex items-center space-x-2">
                          <CheckIcon class="w-4 h-4 text-green-600" />
                          <span>Access contact information</span>
                        </li>
                      </ul>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle class="flex items-center space-x-2">
                    <ClockIcon class="w-5 h-5 text-orange-600" />
                    <span>Schedule Features</span>
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div class="space-y-4">
                    <p class="text-muted-foreground">
                      The schedule system provides comprehensive time management tools for faculty members.
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div class="p-4 border rounded-lg">
                        <h4 class="font-semibold mb-2">Daily View</h4>
                        <p class="text-sm text-muted-foreground">See today's classes and activities at a glance</p>
                      </div>
                      
                      <div class="p-4 border rounded-lg">
                        <h4 class="font-semibold mb-2">Weekly Overview</h4>
                        <p class="text-sm text-muted-foreground">Plan your week with the comprehensive weekly schedule</p>
                      </div>
                      
                      <div class="p-4 border rounded-lg">
                        <h4 class="font-semibold mb-2">Time Tracking</h4>
                        <p class="text-sm text-muted-foreground">Monitor teaching hours and workload distribution</p>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          <!-- Tips & Tricks Tab -->
          <TabsContent value="tips" class="mt-4 overflow-y-auto">
            <div class="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle class="flex items-center space-x-2">
                    <LightBulbIcon class="w-5 h-5 text-yellow-600" />
                    <span>Pro Tips</span>
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div class="space-y-4">
                    <Alert>
                      <InformationCircleIcon class="h-4 w-4" />
                      <AlertTitle>Productivity Tip</AlertTitle>
                      <AlertDescription>
                        Use the academic period selector in the top navigation to quickly switch between semesters and view historical data.
                      </AlertDescription>
                    </Alert>
                    
                    <Alert>
                      <InformationCircleIcon class="h-4 w-4" />
                      <AlertTitle>Navigation Tip</AlertTitle>
                      <AlertDescription>
                        The sidebar navigation adapts to your screen size. On mobile, use the bottom navigation for quick access to main features.
                      </AlertDescription>
                    </Alert>
                    
                    <Alert>
                      <InformationCircleIcon class="h-4 w-4" />
                      <AlertTitle>Efficiency Tip</AlertTitle>
                      <AlertDescription>
                        Use the quick actions dropdown (+ button) in the top navigation for fast access to common tasks like taking attendance or creating assignments.
                      </AlertDescription>
                    </Alert>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Keyboard Shortcuts</CardTitle>
                </CardHeader>
                <CardContent>
                  <div class="space-y-2">
                    <div class="flex justify-between items-center py-2 border-b">
                      <span>Open Teaching Guide</span>
                      <Badge variant="outline">Ctrl + H</Badge>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b">
                      <span>Navigate to Dashboard</span>
                      <Badge variant="outline">Ctrl + D</Badge>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b">
                      <span>View Classes</span>
                      <Badge variant="outline">Ctrl + C</Badge>
                    </div>
                    <div class="flex justify-between items-center py-2">
                      <span>View Students</span>
                      <Badge variant="outline">Ctrl + S</Badge>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          <!-- Support Tab -->
          <TabsContent value="support" class="mt-4 overflow-y-auto">
            <div class="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle class="flex items-center space-x-2">
                    <LifebuoyIcon class="w-5 h-5 text-blue-600" />
                    <span>Get Help</span>
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div class="space-y-4">
                    <p class="text-muted-foreground">
                      Need assistance? Here are the best ways to get help with the Faculty Portal.
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <Button variant="outline" class="h-auto p-4 justify-start" @click="openExternalLink('https://docs.dccphub.com')">
                        <BookOpenIcon class="w-5 h-5 mr-3 text-blue-600" />
                        <div class="text-left">
                          <div class="font-medium">Documentation</div>
                          <div class="text-xs text-muted-foreground">Comprehensive guides and tutorials</div>
                        </div>
                      </Button>
                      
                      <Button variant="outline" class="h-auto p-4 justify-start" @click="openExternalLink('https://github.com/yukazakiri/DccpHubv2/issues')">
                        <ExclamationTriangleIcon class="w-5 h-5 mr-3 text-orange-600" />
                        <div class="text-left">
                          <div class="font-medium">Report Issues</div>
                          <div class="text-xs text-muted-foreground">Submit bug reports and feature requests</div>
                        </div>
                      </Button>
                      
                      <Button variant="outline" class="h-auto p-4 justify-start" @click="openExternalLink('mailto:support@dccphub.com')">
                        <EnvelopeIcon class="w-5 h-5 mr-3 text-green-600" />
                        <div class="text-left">
                          <div class="font-medium">Email Support</div>
                          <div class="text-xs text-muted-foreground">Direct support via email</div>
                        </div>
                      </Button>
                      
                      <Button variant="outline" class="h-auto p-4 justify-start" @click="openExternalLink('https://github.com/yukazakiri/DccpHubv2/discussions')">
                        <ChatBubbleLeftRightIcon class="w-5 h-5 mr-3 text-purple-600" />
                        <div class="text-left">
                          <div class="font-medium">Community</div>
                          <div class="text-xs text-muted-foreground">Join discussions and get community help</div>
                        </div>
                      </Button>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Frequently Asked Questions</CardTitle>
                </CardHeader>
                <CardContent>
                  <Accordion type="single" collapsible class="w-full">
                    <AccordionItem value="item-1">
                      <AccordionTrigger>How do I change my semester settings?</AccordionTrigger>
                      <AccordionContent>
                        Use the academic period selector in the top navigation bar. Click on the current semester/year display and select your desired period from the dropdown menu.
                      </AccordionContent>
                    </AccordionItem>
                    
                    <AccordionItem value="item-2">
                      <AccordionTrigger>Where can I view my complete schedule?</AccordionTrigger>
                      <AccordionContent>
                        Navigate to the "Schedule" section in the sidebar or click "View All" on the schedule widget in your dashboard for a comprehensive view of your weekly schedule.
                      </AccordionContent>
                    </AccordionItem>
                    
                    <AccordionItem value="item-3">
                      <AccordionTrigger>How do I access student information?</AccordionTrigger>
                      <AccordionContent>
                        Go to the "Students" section in the sidebar to view all students you teach. You can filter by class, course, or search by name. Click on any student to view detailed information.
                      </AccordionContent>
                    </AccordionItem>
                    
                    <AccordionItem value="item-4">
                      <AccordionTrigger>Can I use this on mobile devices?</AccordionTrigger>
                      <AccordionContent>
                        Yes! The Faculty Portal is fully responsive and optimized for mobile devices. The interface adapts to provide the best experience on any screen size.
                      </AccordionContent>
                    </AccordionItem>
                  </Accordion>
                </CardContent>
              </Card>
            </div>
          </TabsContent>
        </Tabs>
      </div>

      <DialogFooter class="mt-4">
        <Button variant="outline" @click="$emit('update:open', false)">
          Close Guide
        </Button>
        <Button @click="openExternalLink('https://docs.dccphub.com')">
          <ArrowTopRightOnSquareIcon class="w-4 h-4 mr-2" />
          Full Documentation
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref } from 'vue'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/shadcn/ui/dialog'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/shadcn/ui/tabs'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/shadcn/ui/card'
import { Button } from '@/Components/shadcn/ui/button'
import { Badge } from '@/Components/shadcn/ui/badge'
import { Alert, AlertDescription, AlertTitle } from '@/Components/shadcn/ui/alert'
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/shadcn/ui/accordion'
import {
  BookOpenIcon,
  RocketLaunchIcon,
  HomeIcon,
  AcademicCapIcon,
  CalendarIcon,
  UsersIcon,
  PlayCircleIcon,
  SparklesIcon,
  CheckIcon,
  ClockIcon,
  LightBulbIcon,
  InformationCircleIcon,
  LifebuoyIcon,
  ExclamationTriangleIcon,
  EnvelopeIcon,
  ChatBubbleLeftRightIcon,
  ArrowTopRightOnSquareIcon
} from '@heroicons/vue/24/outline'

defineProps({
  open: {
    type: Boolean,
    default: false
  }
})

defineEmits(['update:open'])

const activeTab = ref('getting-started')

const openExternalLink = (url) => {
  window.open(url, '_blank', 'noopener,noreferrer')
}
</script>
