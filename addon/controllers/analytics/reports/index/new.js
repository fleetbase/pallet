import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';
import { task } from 'ember-concurrency';

export default class AnalyticsReportsIndexNewController extends Controller {
    @service reportActions;
    @service hostRouter;
    @service intl;
    @service notifications;
    @service events;

    @tracked overlay;
    @tracked validationErrors = [];
    @tracked report = this.reportActions.createNewInstance({ type: 'pallet' });

    @task *save(report) {
        try {
            yield report.validate();

            try {
                const result = yield report.execute();
                report.fillResult(result);

                yield report.save();
                this.events.trackResourceCreated(report);
                this.overlay?.close();

                yield this.hostRouter.refresh();
                yield this.hostRouter.transitionTo('console.pallet.analytics.reports.index.details', report);
                this.notifications.success(
                    this.intl.t('common.resource-created-success-name', {
                        resource: this.intl.t('resource.report'),
                        resourceName: report.title,
                    })
                );
                this.resetForm();
            } catch (error) {
                this.notifications.serverError(error);
            }
        } catch (error) {
            if (error.message) {
                this.notifications.error(error?.validation_errors?.firstObject ?? error?.message ?? 'Error validating report configuration');
            } else {
                this.notifications.serverError(error);
            }
        }
    }

    /**
     * Cancel was wired straight to `transition-to`, so the record
     * createNewInstance() had already put in the store was left behind on every
     * cancelled create. They accumulate for the life of the session — visible to
     * anything reading the store rather than the API, and a fresh orphan each time
     * the panel is reopened. Rolling back removes an unsaved record from the store
     * outright.
     */
    @action cancel() {
        const record = this.report;

        if (record?.isNew) {
            record.rollbackAttributes();
        }

        this.overlay?.close();

        return this.hostRouter.transitionTo('console.pallet.analytics.reports.index');
    }

    @action resetForm() {
        this.report = this.reportActions.createNewInstance({ type: 'pallet' });
    }
}
