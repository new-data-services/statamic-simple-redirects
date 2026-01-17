<script setup>
import { ref } from 'vue'
import { Head, router } from '@statamic/cms/inertia'
import { Header, Button, Listing, StatusIndicator, Badge, DropdownItem } from '@statamic/cms/ui'

const props = defineProps({
    title: String,
    redirects: Array,
    columns: Array,
    createUrl: String,
    reorderUrl: String,
    actionUrl: String,
})

const reordering = ref(false)
const reorderedItems = ref(null)

function handleReordered(items) {
    reorderedItems.value = items
}

async function saveOrder() {
    if (! reorderedItems.value) {
        return
    }

    const order = reorderedItems.value.map(item => item.id)

    try {
        const response = await fetch(props.reorderUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': Statamic.$config.get('csrfToken'),
            },
            body: JSON.stringify({ order }),
        })

        if (! response.ok) {
            throw new Error('Failed to save order')
        }

        Statamic.$toast.success(__('simple-redirects::messages.redirects_reordered'))
        reordering.value = false
        reorderedItems.value = null
        router.reload()
    } catch (error) {
        Statamic.$toast.error(__('simple-redirects::messages.order_save_failed'))
        console.error('Save order error:', error)
    }
}

function cancelReorder() {
    reordering.value = false
    reorderedItems.value = null
    router.reload()
}
</script>

<template>
    <Head :title="title" />

    <div class="max-w-6xl mx-auto">
        <Header :title="title" icon="moved">
            <Button
                v-if="! reordering && redirects.length > 1"
                @click="reordering = true"
                :text="__('Reorder')"
            />

            <template v-if="reordering">
                <Button @click="cancelReorder" :text="__('Cancel')" />
                <Button @click="saveOrder" :text="__('Save Order')" variant="primary" />
            </template>

            <Button
                v-if="! reordering"
                :text="__('Create Redirect')"
                :href="createUrl"
                variant="primary"
            />
        </Header>

        <Listing
            v-if="redirects.length > 0"
            :items="redirects"
            :columns="columns"
            :action-url="actionUrl"
            :allow-search="true"
            :allow-customizing-columns="false"
            :allow-presets="false"
            :reorderable="reordering"
            :sortable="false"
            @reordered="handleReordered"
            @refreshing="router.reload()"
        >
            <template #cell-source="{ row, value }">
                <div class="flex items-center gap-2">
                    <StatusIndicator :status="row.enabled ? 'published' : 'draft'" />

                    <a :href="row.edit_url" class="slug-index-field">{{ value }}</a>
                </div>
            </template>

            <template #cell-destination="{ row, value }">
                <a :href="row.edit_url" class="slug-index-field">{{ value }}</a>
            </template>

            <template #cell-type="{ value }">
                <Badge>{{ value }}</Badge>
            </template>

            <template #cell-status_code="{ value }">
                <Badge>{{ value }}</Badge>
            </template>

            <template #prepended-row-actions="{ row }">
                <DropdownItem :text="__('Edit')" icon="pencil" :href="row.edit_url" />
            </template>
        </Listing>

        <div v-if="! redirects.length" class="p-8 text-center text-gray-500">
            <p>{{ __('No redirects found.') }}</p>

            <Button
                :text="__('Create your first redirect')"
                :href="createUrl"
                variant="primary"
                class="mt-4"
            />
        </div>
    </div>
</template>
