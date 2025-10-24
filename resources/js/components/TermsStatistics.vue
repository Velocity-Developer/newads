<template>
    <!-- Statistics Dashboard -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Terms</CardTitle>
                <TrendingUp class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats.total.toLocaleString() }}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">AI Analysis</CardTitle>
                <CheckCircle class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-green-600">{{ stats.ai_relevan }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ stats.ai_negative }} negative, {{ stats.ai_null }} null
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Google Ads</CardTitle>
                <TrendingUp class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-blue-600">{{ stats.google_sukses }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ stats.google_gagal }} gagal, {{ stats.google_error }} error, {{ stats.google_null }} null
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Telegram Notifications</CardTitle>
                <AlertCircle class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-purple-600">{{ stats.telegram_sukses }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ stats.telegram_gagal }} gagal, {{ stats.telegram_null }} null
                </p>
            </CardContent>
        </Card>

        <!-- Frasa Statistics -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Frasa</CardTitle>
                <TrendingUp class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats.frasa_total.toLocaleString() }}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Google Ads (Frasa)</CardTitle>
                <TrendingUp class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-blue-600">{{ stats.frasa_google_sukses }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ stats.frasa_google_gagal }} gagal, {{ stats.frasa_google_error }} error, {{ stats.frasa_google_null }} null
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Telegram (Frasa)</CardTitle>
                <AlertCircle class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-purple-600">{{ stats.frasa_telegram_sukses }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ stats.frasa_telegram_gagal }} gagal, {{ stats.frasa_telegram_null }} null
                </p>
            </CardContent>
        </Card>
    </div>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { TrendingUp, CheckCircle, AlertCircle } from 'lucide-vue-next'

interface StatsProps {
    total: number
    ai_relevan: number
    ai_negative: number
    ai_null: number
    google_sukses: number
    google_gagal: number
    google_error: number
    google_null: number
    telegram_sukses: number
    telegram_gagal: number
    telegram_null: number
    // Frasa stats
    frasa_total: number
    frasa_google_sukses: number
    frasa_google_gagal: number
    frasa_google_error: number
    frasa_google_null: number
    frasa_telegram_sukses: number
    frasa_telegram_gagal: number
    frasa_telegram_null: number
}

defineProps<{
    stats: StatsProps
}>()
</script>