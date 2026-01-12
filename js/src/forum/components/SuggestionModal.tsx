import app from 'flarum/forum/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import Stream from 'flarum/common/utils/Stream';
import Alert from 'flarum/common/components/Alert';
import Category from '../../common/models/Category';

export default class SuggestionModal extends Modal {
  oninit(vnode: any) {
    super.oninit(vnode);

    this.category = this.attrs.category;
    this.name = Stream('');
    this.success = false;
  }

  className() {
    return 'SuggestionModal Modal--small';
  }

  title() {
    return app.translator.trans('huseyinfiliz-awards.forum.other.suggest');
  }

  content() {
    if (this.success) {
        return (
            <div className="Modal-body">
                <div className="Form-group">
                    <Alert type="success">
                        {app.translator.trans('huseyinfiliz-awards.forum.other.success')}
                    </Alert>
                </div>
                <div className="Form-group">
                    <Button className="Button Button--primary" onclick={() => this.hide()}>
                        {app.translator.trans('core.lib.modal.close')}
                    </Button>
                </div>
            </div>
        );
    }

    return (
      <div className="Modal-body">
        <div className="Form-group">
          <label>{app.translator.trans('huseyinfiliz-awards.forum.other.placeholder')}</label>
          <input className="FormControl" bidi={this.name} required />
        </div>

        <div className="Form-group">
          <Button className="Button Button--primary" type="submit" loading={this.loading} disabled={!this.name()}>
            {app.translator.trans('huseyinfiliz-awards.forum.other.submit')}
          </Button>
        </div>
      </div>
    );
  }

  onsubmit(e: any) {
    e.preventDefault();
    this.loading = true;

    app.request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/award-other-suggestions',
        body: {
            data: {
                attributes: {
                    categoryId: this.category.id(),
                    name: this.name()
                }
            }
        },
        errorHandler: this.onerror.bind(this)
    }).then((response: any) => {
      this.success = true;
      this.loading = false;
      m.redraw();
    }).catch(() => {
      this.loading = false;
      m.redraw();
    });
  }
}
