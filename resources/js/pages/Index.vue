<script setup>
import { ref, computed, getCurrentInstance, onBeforeUnmount } from 'vue'
import { Head, router } from '@statamic/cms/inertia'
import { Header, Button, Listing, Badge, Dropdown, DropdownMenu, DropdownItem, StatusIndicator, Icon, EmptyStateMenu, EmptyStateItem } from '@statamic/cms/ui'

const props = defineProps({
    title: String,
    listingUrl: String,
    filters: Array,
    redirectsCount: Number,
    createUrl: String,
    reorderUrl: String,
    actionUrl: String,
    exportUrl: String,
    importUrl: String,
})

const preferencesPrefix = 'simple-redirects'
const instance = getCurrentInstance()
const reordering = ref(false)
const allRedirectsLoaded = ref(false)
const reorderedItems = ref(null)
const listing = ref(null)
const fileInput = ref(null)

const listingParameters = computed(() => reordering.value ? { reordering: 1 } : {})

const saveKeyBinding = ref(Statamic.$keys.bindGlobal(['mod+s'], (e) => {
    if (reordering.value) {
        e.preventDefault()
        saveOrder()
    }
}))

onBeforeUnmount(() => saveKeyBinding.value?.destroy())

function startReorder() {
    allRedirectsLoaded.value = false
    reordering.value = true
}

function handleRequestCompleted() {
    if (reordering.value) {
        allRedirectsLoaded.value = true
    }
}

function handleReordered(items) {
    if (! allRedirectsLoaded.value) {
        return
    }

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
        .catch((error) => {
            if (error.response?.status === 409) {
                Statamic.$toast.error(error.response.data.message)
                cancelReorder()

                return
            }

            Statamic.$toast.error(__('simple-redirects::messages.order_save_failed'))
        })
}

function cancelReorder() {
    reorderedItems.value = null
    reordering.value = false
}

function refreshCount() {
    router.reload({ only: ['redirectsCount'] })
}

function refreshListing() {
    if (! listing.value) {
        router.reload()

        return
    }

    listing.value.refresh()
}

function triggerImport() {
    fileInput.value?.click()
}

function handleImportFile(event) {
    if (! event.target.files[0]) {
        return
    }

    Statamic.$progress.start('import')

    const formData = new FormData()
    formData.append('file', event.target.files[0])

    instance.proxy.$axios.post(props.importUrl, formData)
        .then(() => {
            Statamic.$toast.success(__('simple-redirects::messages.import_complete'))
            refreshListing()
        })
        .catch(() => {
            Statamic.$toast.error(__('simple-redirects::messages.import_failed'))
        })
        .finally(() => {
            Statamic.$progress.complete('import')
            event.target.value = ''
        })
}
</script>

<template>
    <div>
        <Head :title="title" />

        <template v-if="redirectsCount">
            <Header :title="title" icon="moved">
                <Dropdown v-if="! reordering">
                    <template #trigger>
                        <Button icon="dots" variant="ghost" :aria-label="__('Open dropdown menu')" />
                    </template>

                    <DropdownMenu>
                        <DropdownItem
                            :text="__('simple-redirects::messages.import_csv')"
                            icon="upload-arrow-up"
                            @click="triggerImport"
                        />
                        <DropdownItem
                            :text="__('simple-redirects::messages.export_csv')"
                            icon="download-arrow-down"
                            :href="exportUrl"
                            target="_blank"
                            download
                        />
                    </DropdownMenu>
                </Dropdown>

                <Button
                    v-if="! reordering && redirectsCount > 1"
                    @click="startReorder"
                    :text="__('Reorder')"
                />

                <template v-if="reordering">
                    <Button @click="cancelReorder" :text="__('Cancel')" />
                    <Button @click="saveOrder" :text="__('Save Order')" :disabled="! allRedirectsLoaded" variant="primary" />
                </template>

                <Button
                    v-if="! reordering"
                    :text="__('simple-redirects::messages.create_redirect')"
                    :href="createUrl"
                    variant="primary"
                />
            </Header>

            <Listing
                ref="listing"
                :url="listingUrl"
                :additional-parameters="listingParameters"
                :filters="filters"
                :action-url="actionUrl"
                :preferences-prefix="preferencesPrefix"
                :allow-search="true"
                :allow-customizing-columns="true"
                :allow-presets="false"
                :reorderable="reordering"
                :sortable="false"
                :show-pagination-page-links="! reordering"
                :show-pagination-per-page-selector="! reordering"
                @reordered="handleReordered"
                @request-completed="handleRequestCompleted"
                @refreshing="refreshCount"
            >
                <template #cell-source="{ row, value }">
                    <div class="flex items-center gap-2">
                        <StatusIndicator :status="row.enabled ? 'published' : 'draft'" />

                        <a :href="row.edit_url" class="slug-index-field" style="word-break: break-all;">{{ value }}</a>
                    </div>
                </template>

                <template #cell-destination="{ row, value }">
                    <a :href="row.edit_url" class="slug-index-field" style="word-break: break-all;">{{ value }}</a>
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

        <template v-if="! redirectsCount">
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
                    :heading="__('simple-redirects::messages.create_redirect')"
                    :description="__('simple-redirects::messages.create_first_redirect')"
                />
                <EmptyStateItem
                    @click="triggerImport"
                    icon="upload-arrow-up"
                    :heading="__('simple-redirects::messages.import_csv')"
                    :description="__('simple-redirects::messages.import_description')"
                />
            </EmptyStateMenu>
        </template>

        <div class="mt-12 mb-10 flex justify-center text-center">
            <Badge
                :text="__('simple-redirects::messages.learn_about_redirects')"
                icon-append="external-link"
                href="https://statamic.com/addons/new-data-services/simple-redirects"
                target="_blank"
                pill
            />
        </div>

        <input
            ref="fileInput"
            type="file"
            accept=".csv,text/csv"
            class="hidden"
            @change="handleImportFile"
        >
    </div>
</template>
