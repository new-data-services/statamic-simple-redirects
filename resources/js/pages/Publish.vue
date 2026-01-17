<script setup>
import { ref, onMounted, onUnmounted, watch, getCurrentInstance } from 'vue'
import { Head, router } from '@statamic/cms/inertia'
import { Header, Button, ButtonGroup, PublishContainer, PublishTabs, Dropdown, DropdownMenu, DropdownLabel, Radio, RadioGroup } from '@statamic/cms/ui'

const props = defineProps({
    title: String,
    icon: String,
    blueprint: Object,
    values: Object,
    meta: Object,
    submitUrl: String,
    listingUrl: String,
    createAnotherUrl: String,
    editUrlTemplate: String,
    isCreating: { type: Boolean, default: true },
})

const instance = getCurrentInstance()
const formValues = ref(props.values)
const formMeta = ref(props.meta)
const errors = ref({})
const saving = ref(false)

const preferencesPrefix = 'simple-redirects'
const preferencesKey = `${preferencesPrefix}.after_save`
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
    if (value === instance.proxy.$preferences.get(preferencesKey)) return

    value === 'listing'
        ? instance.proxy.$preferences.remove(preferencesKey)
        : instance.proxy.$preferences.set(preferencesKey, value)
})

async function save() {
    saving.value = true
    errors.value = {}

    try {
        const response = await fetch(props.submitUrl, {
            method: props.isCreating ? 'POST' : 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': Statamic.$config.get('csrfToken'),
                'Accept': 'application/json',
            },
            body: JSON.stringify(formValues.value),
        })

        const data = await response.json()

        if (! response.ok) {
            if (response.status === 422) {
                errors.value = data.errors || {}
                Statamic.$toast.error(data.message || __('simple-redirects::messages.validation_failed'))
                return
            }

            Statamic.$toast.error(__('simple-redirects::messages.save_failed'))
            return
        }

        const message = props.isCreating
            ? __('simple-redirects::messages.redirect_created')
            : __('simple-redirects::messages.redirect_updated')

        Statamic.$toast.success(message)
        handleAfterSave(data)
    } finally {
        saving.value = false
    }
}

function handleAfterSave(data) {
    Statamic.$dirty.remove(props.title)

    if (afterSaveOption.value === 'create_another') {
        router.visit(props.createAnotherUrl)

        return
    }

    if (afterSaveOption.value === 'continue_editing') {
        if (props.isCreating && data.id) {
            router.visit(props.editUrlTemplate.replace('{id}', data.id))
        }

        return
    }

    router.visit(props.listingUrl)
}
</script>

<template>
    <Head :title="title" />

    <Header :title="title" :icon="icon">
        <ButtonGroup>
            <Button
                variant="primary"
                :text="__('Save')"
                @click="save"
                :disabled="saving"
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
        :name="title"
        :blueprint="blueprint"
        :meta="formMeta"
        :errors="errors"
        v-model="formValues"
    >
        <PublishTabs />
    </PublishContainer>
</template>
