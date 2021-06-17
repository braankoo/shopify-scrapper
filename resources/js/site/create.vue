<template>
    <div id="create-site">
        <b-card header="New Site">
            <b-form-group
                label="Products HTML"
                label-cols-sm="4"
                content-cols-sm="8"
                label-cols-lg="3"
                content-cols-lg="9"
                class="mb-3"
                label-for="html"
                :invalid-feedback="response.html.feedback"
            >
                <b-input
                    id="html"
                    name="html"
                    @keyup.enter="createSite"
                    v-model="site.html"
                    :state="response.html.state"
                />
            </b-form-group>
            <b-form-group
                label="Products JSON"
                label-cols-sm="4"
                content-cols-sm="8"
                label-cols-lg="3"
                content-cols-lg="9"
                class="mb-3"
                label-for="json"
                :invalid-feedback="response.json.feedback"
            >
                <b-input
                    id="regexp"
                    name="regexp"
                    @keyup.enter="createSite"
                    v-model="site.json"
                    :state="response.json.state"
                />
            </b-form-group>
            <template #footer>
                <b-button :disabled="site.html === ''" variant="success" class="pull-right" @click="createSite">Create
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
                html: '',
                json: ''
            },
            response: {
                html: {
                    state: null,
                    feedback: ''
                },
                json: {
                    state: null,
                    feedback: null
                }
            }
        }
    },
    methods: {
        createSite() {
            if (this.site.html !== '') {
                this.$http.post('/api/site', {
                    ...this.site
                }).then(() => {
                    this.response.html.state = true;
                    this.response.json.state = true;
                    setTimeout(() => {
                        this.response.html.state = null;
                        this.response.json.state = null;
                    }, 3000);

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
        'site.html': function (newVal) {
            if (this.response.html.state || !this.response.html.state) {
                this.response.html.state = null;
                this.response.html.feedback = '';
            }
        }
    }
}
</script>

<style scoped>

</style>
