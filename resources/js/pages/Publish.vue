<script setup>
import { ref, onMounted, onUnmounted, watch, getCurrentInstance, useTemplateRef } from 'vue'
import { Head, router } from '@statamic/cms/inertia'
import { Header, Button, ButtonGroup, PublishContainer, PublishTabs, Dropdown, DropdownMenu, DropdownLabel, DropdownItem, ConfirmationModal, Radio, RadioGroup } from '@statamic/cms/ui'
import { Pipeline, Request, BeforeSaveHooks, AfterSaveHooks } from '@statamic/cms/save-pipeline'

const props = defineProps({
    title: String,
    icon: String,
    blueprint: Object,
    values: Object,
    meta: Object,
    submitUrl: String,
    listingUrl: String,
    createAnotherUrl: String,
    deleteUrl: String,
    isCreating: { type: Boolean, default: true },
})

const instance = getCurrentInstance()
const container = useTemplateRef('container')
const formValues = ref(props.values)
const formMeta = ref(props.meta)
const errors = ref({})
const saving = ref(false)
const showDeleteConfirmation = ref(false)
const deleting = ref(false)

const preferencesKey = 'redirects.after_save'
const afterSaveOption = ref('listing')

let saveKeyBinding

onMounted(() => {
    afterSaveOption.value = instance.proxy.$preferences.get(preferencesKey) || 'listing'

    saveKeyBinding = Statamic.$keys.bindGlobal(['mod+s'], event => {
        event.preventDefault()
        save()
    })
})

onUnmounted(() => saveKeyBinding?.destroy())

watch(afterSaveOption, (value) => {
    if (value === instance.proxy.$preferences.get(preferencesKey)) {
        return
    }

    value === 'listing'
        ? instance.proxy.$preferences.remove(preferencesKey)
        : instance.proxy.$preferences.set(preferencesKey, value)
})

function deleteRedirect() {
    deleting.value = true

    instance.proxy.$axios.delete(props.deleteUrl)
        .then(() => {
            Statamic.$toast.success(__('simple-redirects::messages.redirect_deleted'))
            router.visit(props.listingUrl)
        })
        .catch(() => {
            Statamic.$toast.error(__('Something went wrong'))
            deleting.value = false
            showDeleteConfirmation.value = false
        })
}

function save() {
    new Pipeline()
        .provide({ container, errors, saving })
        .through([
            new BeforeSaveHooks('redirect', { values: formValues.value }),
            new Request(props.submitUrl, props.isCreating ? 'post' : 'patch'),
            new AfterSaveHooks('redirect', { isCreating: props.isCreating }),
        ])
        .then((response) => {
            const message = props.isCreating
                ? __('simple-redirects::messages.redirect_created')
                : __('simple-redirects::messages.redirect_saved')

            Statamic.$toast.success(message)

            if (afterSaveOption.value === 'create_another') {
                router.visit(props.createAnotherUrl)

                return
            }

            if (afterSaveOption.value === 'continue_editing') {
                if (props.isCreating && response.data.redirect) {
                    router.visit(response.data.redirect)
                }

                return
            }

            router.visit(props.listingUrl)
        })
}
</script>

<template>
    <Head :title="title" />

    <Header :title="title" :icon="icon">
        <Dropdown v-if="! isCreating" align="end">
            <template #trigger>
                <Button icon="dots" variant="ghost" :aria-label="__('Open dropdown menu')" />
            </template>

            <DropdownMenu>
                <DropdownItem
                    :text="__('Delete')"
                    icon="trash"
                    variant="destructive"
                    @click="showDeleteConfirmation = true"
                />
            </DropdownMenu>
        </Dropdown>

        <ButtonGroup>
            <Button
                variant="primary"
                :text="__('Save')"
                :disabled="saving"
                @click="save"
            />

            <Dropdown align="end">
                <template #trigger>
                    <Button variant="primary" icon="chevron-down" :aria-label="__('Save options')" />
                </template>

                <DropdownMenu>
                    <DropdownLabel id="after-saving-label" v-text="__('After Saving')" />

                    <RadioGroup v-model="afterSaveOption" aria-labelledby="after-saving-label">
                        <Radio :label="__('Go To Listing')" value="listing" />
                        <Radio :label="__('Continue Editing')" value="continue_editing" />
                        <Radio :label="__('Create Another')" value="create_another" />
                    </RadioGroup>
                </DropdownMenu>
            </Dropdown>
        </ButtonGroup>
    </Header>

    <PublishContainer
        ref="container"
        :name="title"
        :blueprint="blueprint"
        :meta="formMeta"
        :errors="errors"
        v-model="formValues"
    >
        <PublishTabs />
    </PublishContainer>

    <ConfirmationModal
        v-model:open="showDeleteConfirmation"
        :title="__('Delete')"
        :body-text="__('simple-redirects::messages.delete_confirmation')"
        :button-text="__('Delete')"
        :busy="deleting"
        danger
        @confirm="deleteRedirect"
    />
</template>
