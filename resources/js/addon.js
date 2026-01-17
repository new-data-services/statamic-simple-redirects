import Index from './pages/Index.vue'
import Publish from './pages/Publish.vue'

Statamic.booting(() => {
    Statamic.$inertia.register('simple-redirects::Index', Index)
    Statamic.$inertia.register('simple-redirects::Publish', Publish)
})
