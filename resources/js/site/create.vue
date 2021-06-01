<template>
    <div id="create-site">
        <b-card header="New Site">
            <b-form-group
                label="Site Url"
                label-cols-sm="4"
                content-cols-sm="8"
                label-cols-lg="3"
                content-cols-lg="9"
                class="mb-3"
                label-for="answer"
                :invalid-feedback="response.url.feedback"
            >
                <b-input
                    id="actor"
                    name="actor"
                    @keyup.enter="createSite"
                    v-model="site.url"
                    :state="response.url.state"
                />
            </b-form-group>
            <b-form-group
                label="Site Regexp"
                label-cols-sm="4"
                content-cols-sm="8"
                label-cols-lg="3"
                content-cols-lg="9"
                class="mb-3"
                label-for="answer"
                :invalid-feedback="response.regexp.feedback"
            >
                <b-input
                    id="regexp"
                    name="regexp"
                    @keyup.enter="createSite"
                    v-model="site.regexp"
                    :state="response.regexp.state"
                />
            </b-form-group>
            <template #footer>
                <b-button :disabled="site.url === ''" variant="success" class="pull-right" @click="createSite">Create
                    New
                </b-button>
            </template>
        </b-card>
    </div>
</template>

<script>
export default {
    name: "ActorCreate",
    data() {
        return {
            site: {
                url: '',
                regexp: ''
            },
            response: {
                url: {
                    state: null,
                    feedback: ''
                },
                regexp: {
                    state: null,
                    feedback: null
                }
            }
        }
    },
    methods: {
        createSite() {
            if (this.site.url !== '') {
                this.$http.post('/api/site', {
                    ...this.site
                }).then(() => {
                    this.response.url.state = true;

                }).catch((error) => {
                    const errors = error.response.data.errors;
                    if (error.response.status === 422) {
                        for (let error in errors) {
                            if (errors.hasOwnProperty(error)) {
                                this.response[error].feedback = errors[error].join(' ');
                                this.response[error].state = false;
                            }
                        }
                        this.submitting = false;
                    }
                })
            }

        }
    },
    watch: {
        'site.url': function (newVal) {
            if (this.response.url.state || !this.response.url.state) {
                this.response.url.state = null;
                this.response.url.feedback = '';
            }
        }
    }
}
</script>

<style scoped>

</style>
