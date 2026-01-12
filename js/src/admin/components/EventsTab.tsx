import Component from 'flarum/common/Component';
import extractText from 'flarum/common/utils/extractText';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Placeholder from 'flarum/common/components/Placeholder';
import Button from 'flarum/common/components/Button';
import EventModal from './modals/EventModal';
import ResultModal from './modals/ResultModal';
import PickemEvent from '../../common/models/Event';
import Team from '../../common/models/Team';
import Season from '../../common/models/Season';
import Week from '../../common/models/Week';

export default class EventsTab extends Component {
  private selectedSeason: string = 'all';
  private selectedTeam: string = 'all';
  private selectedStatus: string = 'all';
  private sortOrder: string = 'desc';
  
  private loading: boolean = false;
  private events: PickemEvent[] = [];
  private hasMore: boolean = false;
  private limit: number = 20;
  private page: number = 1;

  oninit(vnode: any) {
    super.oninit(vnode);
    this.loadEvents(true);
  }

  buildFilters() {
    const filters: any = {};
    const weekIds: string[] = [];

    if (this.selectedSeason !== 'all') {
      const weeks = app.store.all<Week>('pickem-weeks').filter(week => week.seasonId() == this.selectedSeason);
      weekIds.push(...weeks.map(week => String(week.id())));
      if (weekIds.length === 0) weekIds.push('0');
      // Admin panelinde de haftasız maçları dahil etmek isteyebiliriz
      weekIds.push('null');
      filters.week = weekIds.join(',');
    }

    if (this.selectedTeam !== 'all') {
      filters.team = this.selectedTeam;
    }

    if (this.selectedStatus !== 'all') {
      filters.status = this.selectedStatus;
    }

    return filters;
  }

  loadEvents(clear: boolean = false) {
    this.loading = true;
    m.redraw();

    const offset = clear ? 0 : this.events.length;
    const filters = this.buildFilters();
    const sort = this.sortOrder === 'desc' ? '-matchDate' : 'matchDate';

    app.store.find<PickemEvent[]>('pickem-events', {
      include: 'homeTeam,awayTeam,week',
      filter: filters,
      sort: sort,
      page: { limit: this.limit, offset: offset }
    }).then((results) => {
      if (clear) {
        this.events = results;
      } else {
        this.events = [...this.events, ...results];
      }
      
      this.hasMore = results.length >= this.limit;
      this.loading = false;
      m.redraw();
    }).catch(err => {
      this.loading = false;
      console.error(err);
      m.redraw();
    });
  }

  view() {
    const seasons = app.store.all<Season>('pickem-seasons');
    const teams = app.store.all<Team>('pickem-teams');
    const hasEvents = this.events.length > 0;


    const reverse = app.data.settings['huseyinfiliz-pickem.reverse_display'] === '1';

    return (
      <div className="EventsTab">
        <div className="EventsTab-header">
          <h3>
            <i className="fas fa-calendar-alt" />
            {app.translator.trans('huseyinfiliz-pickem.lib.nav.matches')}
          </h3>
          <Button
            className="Button Button--primary"
            icon="fas fa-plus"
            onclick={() => app.modal.show(EventModal, { 
              event: null, 
              onsave: () => this.loadEvents(true) 
            })}
          >
            {app.translator.trans('huseyinfiliz-pickem.lib.actions.create')}
          </Button>
        </div>

        <div className="EventsTab-filters">
           <div className="FormGroup">
            <label>{app.translator.trans('huseyinfiliz-pickem.lib.common.season')}</label>
            <select
              value={this.selectedSeason}
              onchange={(e: any) => {
                this.selectedSeason = e.target.value;
                this.loadEvents(true);
              }}
            >
              <option value="all">{app.translator.trans('huseyinfiliz-pickem.lib.filters.all')}</option>
              {seasons.map(season => (
                <option key={season.id()} value={season.id()}>
                  {season.name()}
                </option>
              ))}
            </select>
          </div>
          
          <div className="FormGroup">
            <label>{app.translator.trans('huseyinfiliz-pickem.lib.common.team')}</label>
            <select
              value={this.selectedTeam}
              onchange={(e: any) => {
                this.selectedTeam = e.target.value;
                this.loadEvents(true);
              }}
            >
              <option value="all">{app.translator.trans('huseyinfiliz-pickem.lib.filters.all')}</option>
              {teams.map(team => (
                <option key={team.id()} value={team.id()}>
                  {team.name()}
                </option>
              ))}
            </select>
          </div>

          <div className="FormGroup">
            <label>{app.translator.trans('huseyinfiliz-pickem.lib.common.status')}</label>
            <select
              value={this.selectedStatus}
              onchange={(e: any) => {
                this.selectedStatus = e.target.value;
                this.loadEvents(true);
              }}
            >
              <option value="all">{app.translator.trans('huseyinfiliz-pickem.lib.filters.all')}</option>
              <option value="scheduled">{app.translator.trans('huseyinfiliz-pickem.lib.status.scheduled')}</option>
              <option value="closed">{app.translator.trans('huseyinfiliz-pickem.lib.status.closed')}</option>
              <option value="finished">{app.translator.trans('huseyinfiliz-pickem.lib.status.finished')}</option>
            </select>
          </div>

          <div className="FormGroup">
            <label>{app.translator.trans('huseyinfiliz-pickem.lib.filters.sort')}</label>
            <select
              value={this.sortOrder}
              onchange={(e: any) => {
                this.sortOrder = e.target.value;
                this.loadEvents(true);
              }}
            >
              <option value="desc">{app.translator.trans('huseyinfiliz-pickem.lib.filters.newest')}</option>
              <option value="asc">{app.translator.trans('huseyinfiliz-pickem.lib.filters.oldest')}</option>
            </select>
          </div>
        </div>

        {!hasEvents && !this.loading ? (
          <div className="EmptyState">
            <i className="fas fa-calendar-times" />
            <p>{app.translator.trans('huseyinfiliz-pickem.lib.messages.no_matches')}</p>
          </div>
        ) : (
          <div className="CardList">
            <div className="CardList-header">
              <div>{reverse ? app.translator.trans('huseyinfiliz-pickem.lib.common.away') : app.translator.trans('huseyinfiliz-pickem.lib.common.home')}</div>
              <div>{reverse ? app.translator.trans('huseyinfiliz-pickem.lib.common.home') : app.translator.trans('huseyinfiliz-pickem.lib.common.away')}</div>
              <div>{app.translator.trans('huseyinfiliz-pickem.lib.headers.match_date')}</div>
              <div>{app.translator.trans('huseyinfiliz-pickem.lib.common.status')}</div>
              <div>{app.translator.trans('huseyinfiliz-pickem.lib.common.score')}</div>
              <div></div>
            </div>

            {this.events.map((event) => {
              const homeTeam = event.homeTeam() as Team | false;
              const awayTeam = event.awayTeam() as Team | false;

              return (
                <div key={event.id()} className="CardList-item">

                  <div className="CardList-item-cell CardList-item-cell--primary" data-label={reverse ? app.translator.trans('huseyinfiliz-pickem.lib.common.away') : app.translator.trans('huseyinfiliz-pickem.lib.common.home')}>
                    <div className="TeamCell">
                      {this.renderTeamLogo(reverse ? awayTeam : homeTeam)}
                      <span>{reverse ? (awayTeam ? awayTeam.name() : 'N/A') : (homeTeam ? homeTeam.name() : 'N/A')}</span>
                    </div>
                  </div>

                  <div className="CardList-item-cell" data-label={reverse ? app.translator.trans('huseyinfiliz-pickem.lib.common.home') : app.translator.trans('huseyinfiliz-pickem.lib.common.away')}>
                    <div className="TeamCell">
                      {this.renderTeamLogo(reverse ? homeTeam : awayTeam)}
                      <span>{reverse ? (homeTeam ? homeTeam.name() : 'N/A') : (awayTeam ? awayTeam.name() : 'N/A')}</span>
                    </div>
                  </div>

                  <div className="CardList-item-cell CardList-item-cell--muted" data-label={app.translator.trans('huseyinfiliz-pickem.lib.headers.match_date')}>
                    {event.matchDate() ? new Date(event.matchDate()!).toLocaleString() : '-'}
                  </div>

                  <div className="CardList-item-cell" data-label={app.translator.trans('huseyinfiliz-pickem.lib.common.status')}>
                    <span className={`StatusBadge StatusBadge--${event.status()}`}>
                      {app.translator.trans(`huseyinfiliz-pickem.lib.status.${event.status()}`)}
                    </span>
                  </div>

                  <div className="CardList-item-cell" data-label={app.translator.trans('huseyinfiliz-pickem.lib.common.score')}>
                    {event.homeScore() !== null && event.awayScore() !== null
                      ? (reverse ? `${event.awayScore()} - ${event.homeScore()}` : `${event.homeScore()} - ${event.awayScore()}`)
                      : '-'}
                  </div>

                  <div className="CardList-item-actions">
                    <Button
                      className="Button Button--primary"
                      icon="fas fa-edit"
                      onclick={() => app.modal.show(EventModal, { 
                        event: event, 
                        onsave: () => this.loadEvents(true) 
                      })}
                    >
                      {app.translator.trans('huseyinfiliz-pickem.lib.buttons.edit')}
                    </Button>
                    <Button
                      className="Button Button--success"
                      icon="fas fa-check"
                      onclick={() => app.modal.show(ResultModal, { 
                        event: event, 
                        onsave: () => this.loadEvents(true) 
                      })}
                    >
                      {app.translator.trans('huseyinfiliz-pickem.lib.actions.enter_result')}
                    </Button>
                    <Button
                      className="Button Button--danger"
                      icon="fas fa-trash"
                      onclick={() => this.deleteEvent(event)}
                    >
                      {app.translator.trans('huseyinfiliz-pickem.lib.buttons.delete')}
                    </Button>
                  </div>
                </div>
              );
            })}
          </div>
        )}

        {this.loading && <LoadingIndicator />}

        {this.hasMore && !this.loading && (
          <div className="LoadMore">
            <Button
              className="Button Button--primary"
              onclick={() => this.loadEvents(false)}
            >
              {app.translator.trans('huseyinfiliz-pickem.lib.buttons.load_more')}
            </Button>
          </div>
        )}
      </div>
    );
  }

  renderTeamLogo(team: Team | false) {
    if (!team) {
      return (
        <div className="TeamLogo TeamLogo--letter" style={{ backgroundColor: '#999' }}>?</div>
      );
    }

    const logoUrl = team.logoUrl();
    const teamName = team.name();
    const firstLetter = teamName ? teamName.charAt(0).toUpperCase() : '?';

    const stringToColor = (str: string) => {
      let hash = 0;
      for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
      }
      const hue = hash % 360;
      return `hsl(${hue}, 65%, 50%)`;
    };

    if (logoUrl) {
      return (
        <img
          src={logoUrl}
          alt={teamName || 'Team'}
          className="TeamLogo TeamLogo--image"
        />
      );
    } else {
      const backgroundColor = stringToColor(teamName || 'Team');
      return (
        <div className="TeamLogo TeamLogo--letter" style={{ backgroundColor }}>
          {firstLetter}
        </div>
      );
    }
  }

  deleteEvent(event: PickemEvent) {
    const confirmMessage = extractText(app.translator.trans('huseyinfiliz-pickem.lib.messages.delete_confirm'));
    
    if (confirm(confirmMessage)) {
      event.delete().then(() => {
        this.loadEvents(true);
      });
    }
  }
}