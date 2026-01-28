<script setup>
import { ref, getCurrentInstance } from 'vue'
import { Head, router } from '@statamic/cms/inertia'
import { Header, Button, Listing, Badge, DropdownItem, StatusIndicator, Icon, EmptyStateMenu, EmptyStateItem } from '@statamic/cms/ui'

const props = defineProps({
    title: String,
    redirects: Array,
    columns: Array,
    createUrl: String,
    reorderUrl: String,
    actionUrl: String,
})

const preferencesPrefix = 'simple-redirects'
const instance = getCurrentInstance()
const reordering = ref(false)
const reorderedItems = ref(null)
const listingKey = ref(0)

function handleReordered(items) {
    reorderedItems.value = items
}

function saveOrder() {
    if (! reorderedItems.value) {
        reordering.value = false

        return
    }

    const order = reorderedItems.value.map(item => item.id)

    instance.proxy.$axios.post(props.reorderUrl, { order })
        .then(() => {
            Statamic.$toast.success(__('simple-redirects::messages.redirects_reordered'))
            reorderedItems.value = null
            reordering.value = false
        })
        .catch(() => {
            Statamic.$toast.error(__('simple-redirects::messages.order_save_failed'))
        })
}

function cancelReorder() {
    reorderedItems.value = null
    reordering.value = false
    listingKey.value++
}
</script>

<template>
    <Head :title="title" />

    <div class="max-w-6xl mx-auto">
        <template v-if="redirects.length">
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
                :key="listingKey"
                :items="redirects"
                :columns="columns"
                :action-url="actionUrl"
                :preferences-prefix="preferencesPrefix"
                :allow-search="true"
                :allow-customizing-columns="true"
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

                <template #cell-sites="{ value }">
                    {{ value }}
                </template>

                <template #cell-regex="{ value }">
                    <Badge size="sm" v-if="value">Regex</Badge>
                </template>

                <template #cell-status_code="{ value }">
                    <Badge size="sm">{{ value }}</Badge>
                </template>

                <template #prepended-row-actions="{ row }">
                    <DropdownItem :text="__('Edit')" icon="pencil" :href="row.edit_url" />
                </template>
            </Listing>
        </template>

        <template v-if="! redirects.length">
            <header class="py-8 mt-8 text-center">
                <h1 class="text-[25px] font-medium antialiased flex justify-center items-center gap-2 sm:gap-3">
                    <Icon name="moved" class="size-5 text-gray-500" />
                    {{ title }}
                </h1>
            </header>

            <EmptyStateMenu :heading="__('simple-redirects::messages.redirects_intro')">
                <EmptyStateItem
                    :href="createUrl"
                    icon="moved"
                    :heading="__('Create Redirect')"
                    :description="__('simple-redirects::messages.create_first_redirect')"
                />
            </EmptyStateMenu>

            <div class="mt-12 mb-10 flex justify-center text-center">
                <Badge
                    :text="__('simple-redirects::messages.learn_about_redirects')"
                    icon-append="external-link"
                    href="https://github.com/new-data-services/statamic-simple-redirects"
                    target="_blank"
                    pill
                />
            </div>
        </template>
    </div>
</template>
