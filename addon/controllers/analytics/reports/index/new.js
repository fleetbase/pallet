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

    @action resetForm() {
        this.report = this.reportActions.createNewInstance({ type: 'pallet' });
    }
}
