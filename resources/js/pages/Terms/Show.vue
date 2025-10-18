<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, RefreshCw, MessageSquare, TrendingUp, Eye } from 'lucide-vue-next';

interface FrasaNegative {
    id: number;
    frasa: string;
    created_at: string;
    updated_at: string;
}

interface Term {
    id: number;
    terms: string;
    hasil_cek_ai: 'positive' | 'negative' | 'pending';
    status_input_google: 'success' | 'failed' | 'pending';
    notif_telegram: 'sent' | 'failed' | 'pending';
    retry_count: number;
    created_at: string;
    updated_at: string;
    frasa_negatives: FrasaNegative[];
}

interface Props {
    term: Term;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Terms Management',
        href: '/terms',
    },
    {
        title: props.term.terms,
        href: `/terms/${props.term.id}`,
    },
];

// Badge variants for different statuses
const getAiBadgeVariant = (status: string) => {
    switch (status) {
        case 'positive': return 'default';
        case 'negative': return 'destructive';
        case 'pending': return 'secondary';
        default: return 'secondary';
    }
};

const getGoogleBadgeVariant = (status: string) => {
    switch (status) {
        case 'success': return 'default';
        case 'failed': return 'destructive';
        case 'pending': return 'secondary';
        default: return 'secondary';
    }
};

const getTelegramBadgeVariant = (status: string) => {
    switch (status) {
        case 'sent': return 'default';
        case 'failed': return 'destructive';
        case 'pending': return 'secondary';
        default: return 'secondary';
    }
};

// Format date
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Get status icon
const getStatusIcon = (status: string) => {
    switch (status) {
        case 'positive':
        case 'success':
        case 'sent':
            return 'text-green-600';
        case 'negative':
        case 'failed':
            return 'text-red-600';
        case 'pending':
        default:
            return 'text-yellow-600';
    }
};
</script>

<template>
    <Head :title="`Term: ${term.terms}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        href="/terms"
                        class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        Back to Terms
                    </Link>
                </div>
            </div>

            <!-- Term Details -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Eye class="h-5 w-5" />
                        Term Details
                    </CardTitle>
                    <CardDescription>
                        Detailed information about this term and its processing status
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Term Name -->
                    <div>
                        <h2 class="text-2xl font-bold mb-2">{{ term.terms }}</h2>
                        <p class="text-muted-foreground">Term ID: #{{ term.id }}</p>
                    </div>

                    <Separator />

                    <!-- Status Overview -->
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <div :class="['w-3 h-3 rounded-full', getStatusIcon(term.hasil_cek_ai)]"></div>
                                <span class="text-sm font-medium">AI Analysis Result</span>
                            </div>
                            <Badge :variant="getAiBadgeVariant(term.hasil_cek_ai)" class="text-sm">
                                {{ term.hasil_cek_ai.toUpperCase() }}
                            </Badge>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <div :class="['w-3 h-3 rounded-full', getStatusIcon(term.status_input_google)]"></div>
                                <span class="text-sm font-medium">Google Ads Status</span>
                            </div>
                            <Badge :variant="getGoogleBadgeVariant(term.status_input_google)" class="text-sm">
                                {{ term.status_input_google.toUpperCase() }}
                            </Badge>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <div :class="['w-3 h-3 rounded-full', getStatusIcon(term.notif_telegram)]"></div>
                                <span class="text-sm font-medium">Telegram Notification</span>
                            </div>
                            <Badge :variant="getTelegramBadgeVariant(term.notif_telegram)" class="text-sm">
                                {{ term.notif_telegram.toUpperCase() }}
                            </Badge>
                        </div>
                    </div>

                    <Separator />

                    <!-- Additional Information -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <RefreshCw class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm font-medium">Retry Count</span>
                            </div>
                            <p class="text-2xl font-bold">{{ term.retry_count }}</p>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <MessageSquare class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm font-medium">Negative Phrases</span>
                            </div>
                            <p class="text-2xl font-bold">{{ term.frasa_negatives.length }}</p>
                        </div>
                    </div>

                    <Separator />

                    <!-- Timestamps -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm font-medium">Created At</span>
                            </div>
                            <p class="text-sm text-muted-foreground">{{ formatDate(term.created_at) }}</p>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm font-medium">Last Updated</span>
                            </div>
                            <p class="text-sm text-muted-foreground">{{ formatDate(term.updated_at) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Negative Phrases -->
            <Card v-if="term.frasa_negatives.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <MessageSquare class="h-5 w-5" />
                        Negative Phrases
                    </CardTitle>
                    <CardDescription>
                        List of negative phrases associated with this term ({{ term.frasa_negatives.length }} total)
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div
                            v-for="frasa in term.frasa_negatives"
                            :key="frasa.id"
                            class="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50"
                        >
                            <div class="flex-1">
                                <p class="font-medium">{{ frasa.frasa }}</p>
                                <p class="text-sm text-muted-foreground">
                                    Added on {{ formatDate(frasa.created_at) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Empty State for Negative Phrases -->
            <Card v-else>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <MessageSquare class="h-5 w-5" />
                        Negative Phrases
                    </CardTitle>
                    <CardDescription>
                        No negative phrases found for this term
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="text-center py-8">
                        <MessageSquare class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                        <p class="text-muted-foreground">No negative phrases have been added to this term yet.</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>