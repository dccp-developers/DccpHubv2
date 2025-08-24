<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/shadcn/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/shadcn/ui/dropdown-menu';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/Components/shadcn/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/shadcn/ui/select';
import { toast } from 'vue-sonner';

const page = usePage();
const isOpen = ref(false);
const isLoading = ref(false);
const settings = ref({
    current_semester: 1,
    current_school_year: new Date().getFullYear(),
    current_school_year_string: '',
    available_semesters: { 1: '1st Semester', 2: '2nd Semester' },
    available_school_years: {}
});

const selectedSemester = ref(1);
const selectedSchoolYear = ref(new Date().getFullYear());

// Check if user is a student
const isStudent = computed(() => {
    return page.props.auth?.user?.role === 'student';
});

// Load current settings
const loadSettings = async () => {
    if (!isStudent.value) return;
    
    try {
        const response = await fetch('/student/settings/', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (response.ok) {
            const data = await response.json();
            if (data.success) {
                settings.value = data.data;
                selectedSemester.value = data.data.current_semester;
                selectedSchoolYear.value = data.data.current_school_year;
            }
        }
    } catch (error) {
        console.error('Failed to load settings:', error);
    }
};

// Update settings
const updateSettings = async () => {
    if (!isStudent.value) return;
    
    isLoading.value = true;
    
    try {
        const response = await fetch('/student/settings/both', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                semester: selectedSemester.value,
                school_year: selectedSchoolYear.value,
            }),
        });

        const data = await response.json();

        if (data.success) {
            settings.value.current_semester = selectedSemester.value;
            settings.value.current_school_year = selectedSchoolYear.value;
            settings.value.current_school_year_string = data.data.school_year_string;
            
            toast.success('Settings updated successfully');
            isOpen.value = false;
            
            // Refresh the page to show updated data
            router.reload();
        } else {
            toast.error(data.message || 'Failed to update settings');
        }
    } catch (error) {
        console.error('Failed to update settings:', error);
        toast.error('Failed to update settings');
    } finally {
        isLoading.value = false;
    }
};

// Format semester display
const currentSemesterDisplay = computed(() => {
    return settings.value.available_semesters[settings.value.current_semester] || '1st Semester';
});

// Format school year display
const currentSchoolYearDisplay = computed(() => {
    return settings.value.current_school_year_string || `${settings.value.current_school_year} - ${settings.value.current_school_year + 1}`;
});

// Format compact display for mobile and dropdown
const currentPeriodCompactDisplay = computed(() => {
    const semester = settings.value.available_semesters[settings.value.current_semester] || '1st Semester';
    const schoolYear = settings.value.current_school_year_string || `${settings.value.current_school_year} - ${settings.value.current_school_year + 1}`;
    
    // Extract semester number and format as "1st Sem", "2nd Sem"
    const semesterMatch = semester.match(/^(\d+)(st|nd|rd|th)/);
    if (semesterMatch) {
        const semesterNum = semesterMatch[1];
        const suffix = semesterMatch[2];
        return `${semesterNum}${suffix} Sem, ${schoolYear}`;
    }
    
    // Fallback if regex doesn't match
    return `${semester}, ${schoolYear}`;
});

onMounted(() => {
    loadSettings();
});
</script>

<template>
    <div v-if="isStudent" class="flex items-center">
        <!-- Desktop Version -->
        <div class="hidden md:block">
            <Dialog v-model:open="isOpen">
                <DialogTrigger asChild>
                    <Button variant="ghost" size="sm" class="flex items-center gap-2 text-sm">
                        <Icon icon="lucide:calendar" class="w-4 h-4" />
                        <span class="font-medium">{{ currentPeriodCompactDisplay }}</span>
                        <Icon icon="lucide:chevron-down" class="w-4 h-4" />
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2">
                            <Icon icon="lucide:settings" class="w-5 h-5" />
                            Academic Period Settings
                        </DialogTitle>
                        <DialogDescription>
                            Change your current semester and school year to view different academic periods.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div class="space-y-4 py-4">
                        <!-- Semester Selection -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Semester</label>
                            <Select v-model="selectedSemester">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select semester" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem 
                                        v-for="(name, value) in settings.available_semesters" 
                                        :key="value" 
                                        :value="parseInt(value)"
                                    >
                                        {{ name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- School Year Selection -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">School Year</label>
                            <Select v-model="selectedSchoolYear">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select school year" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem 
                                        v-for="(display, value) in settings.available_school_years" 
                                        :key="value" 
                                        :value="parseInt(value)"
                                    >
                                        {{ display }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end gap-2 pt-4">
                            <Button variant="outline" @click="isOpen = false" :disabled="isLoading">
                                Cancel
                            </Button>
                            <Button @click="updateSettings" :disabled="isLoading">
                                <Icon v-if="isLoading" icon="lucide:loader-2" class="w-4 h-4 mr-2 animate-spin" />
                                Update
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </div>

        <!-- Mobile Version -->
        <div class="md:hidden">
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="sm" class="flex items-center gap-1 text-xs px-2">
                        <Icon icon="lucide:calendar" class="w-3 h-3" />
                        <span class="truncate max-w-24">{{ currentPeriodCompactDisplay }}</span>
                        <Icon icon="lucide:chevron-down" class="w-3 h-3" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-56">
                    <DropdownMenuLabel class="text-xs">Current Period</DropdownMenuLabel>
                    <DropdownMenuItem disabled class="text-xs">
                        <div class="flex flex-col">
                            <span>{{ currentPeriodCompactDisplay }}</span>
                        </div>
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @click="isOpen = true" class="text-xs">
                        <Icon icon="lucide:settings" class="w-3 h-3 mr-2" />
                        Change Period
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </div>
</template>
