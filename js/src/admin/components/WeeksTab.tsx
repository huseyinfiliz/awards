import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import WeekModal from './modals/WeekModal'; 
import Week from '../../common/models/Week'; 
import Season from '../../common/models/Season';
import extractText from 'flarum/common/utils/extractText';

export default class WeeksTab extends Component {
  view() {
    const weeks = app.store.all<Week>('pickem-weeks');

    return (
      <div className="WeeksTab">
        <div className="WeeksTab-header">
          <h3>
            <i className="fas fa-calendar-week" />
            {app.translator.trans('huseyinfiliz-pickem.lib.nav.weeks')}
          </h3>
          <Button
            className="Button Button--primary"
            icon="fas fa-plus"
            onclick={() => app.modal.show(WeekModal, {
              week: null,
              onsave: () => m.redraw() 
            })}
          >
            {/* GÜNCELLENDİ: Parametre kaldırıldı */}
            {app.translator.trans('huseyinfiliz-pickem.lib.actions.create')}
          </Button>
        </div>

        <div className="CardList">
          <div className="CardList-header">
            <div>{app.translator.trans('huseyinfiliz-pickem.lib.headers.name')}</div>
            <div>{app.translator.trans('huseyinfiliz-pickem.lib.common.season')}</div>
            <div>{app.translator.trans('huseyinfiliz-pickem.lib.headers.week_number')}</div>
            <div></div>
          </div>

          {weeks.length === 0 ? (
            <div className="EmptyState">
              <i className="fas fa-calendar-times" />
              <p>{app.translator.trans('huseyinfiliz-pickem.lib.messages.no_data')}</p>
            </div>
          ) : (
            weeks.map(week => {
              const season = week.season() as Season | false;
              return (
                <div key={week.id()} className="CardList-item">
                  <div className="CardList-item-cell CardList-item-cell--primary" data-label={app.translator.trans('huseyinfiliz-pickem.lib.headers.name')}>
                    {week.name()}
                  </div>

                  <div className="CardList-item-cell" data-label={app.translator.trans('huseyinfiliz-pickem.lib.common.season')}>
                    {season ? season.name() : '-'}
                  </div>

                  <div className="CardList-item-cell CardList-item-cell--muted" data-label={app.translator.trans('huseyinfiliz-pickem.lib.headers.week_number')}>
                    {week.weekNumber()}
                  </div>

                  <div className="CardList-item-actions">
                    <Button
                      className="Button Button--primary"
                      icon="fas fa-edit"
                      onclick={() => app.modal.show(WeekModal, {
                        week: week,
                        onsave: () => m.redraw()
                      })}
                    >
                      {app.translator.trans('huseyinfiliz-pickem.lib.buttons.edit')}
                    </Button>
                    <Button
                      className="Button Button--danger"
                      icon="fas fa-trash"
                      onclick={() => this.deleteWeek(week)}
                    >
                      {app.translator.trans('huseyinfiliz-pickem.lib.buttons.delete')}
                    </Button>
                  </div>
                </div>
              );
            })
          )}
        </div>
      </div>
    );
  }

  deleteWeek(week: Week) {
    // GÜNCELLENDİ: Parametre kaldırıldı
    const confirmMessage = extractText(app.translator.trans('huseyinfiliz-pickem.lib.messages.delete_confirm'));

    if (!confirm(confirmMessage)) {
      return;
    }
    week.delete().then(() => {
      m.redraw();
    });
  }
}