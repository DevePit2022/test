<?php
declare(strict_types=1);

namespace App\Controller\Panel;

use App\Event\MemberEvents;
use App\Traits\RequestTrait;
use App\Traits\ViewBuilderTrait;
use Cake\Http\Response;

/**
 * Members Controller
 *
 * @property \App\Model\Table\AreasTable          $Areas
 * @property \App\Model\Table\ConsultantsTable    $Consultants
 * @property \App\Model\Table\CountriesTable      $Countries
 * @property \App\Model\Table\DivisionsTable      $Divisions
 * @property \App\Model\Table\MembersTable        $Members
 * @property \App\Model\Table\MemberStatusesTable $MemberStatuses
 * @property \App\Model\Table\RegionsTable        $Regions
 * @description Members/Clients module.
 */
class MembersController extends PanelController
{
    use RequestTrait;
    use ViewBuilderTrait;

    /**
     * Initialize
     *
     * @throws \Exception
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->Panel->setHeadingValue('title', '');
        $this->Panel->setHeadingValue('panel', __('Clients'));

        $this->getEventManager()->on(new MemberEvents());
    }

    /**
     * Index method
     *
     * @description Clients list.
     * @return void
     */
    public function index(): void
    {
        $this->setLayout('clean');
    }

    /**
     * View Method
     *
     * @param string $id Row id.
     * @description Client view details.
     * @return void
     */
    public function view(string $id): ?Response
    {
        $this->Panel->setHeadingValue('title', __('Client'));
        $this->Panel->setHeadingValue('subtitle', '');

        $this->setLayout('clean');
        $member = $this->Members->get($id, [
            'contain' => ['MemberContacts', 'MemberDetails', 'MemberAddresses' => ['Countries']],
        ]);
        $member->member_contact = $member->member_address = [];
        if ($member->has('member_addresses')) {
            $member->member_address = end($member->member_addresses);
            unset($member->member_addresses);
        }
        if ($member->has('member_contacts')) {
            $member->member_contact = end($member->member_contacts);
            unset($member->member_contacts);
        }

        $this->loadModel('MembersVacancies');
        $stats = $this->MembersVacancies->stats((int)$id);
        $this->set(compact('member', 'stats'));

        return null;
    }

    /**
     * Edit Method
     *
     * @param string $id Row id.
     * @description Edit client.
     * @return void
     */
    public function edit(string $id): ?Response
    {
        $this->setLayout('clean');
        $this->loadModel('Areas');
        $this->loadModel('Countries');
        $this->loadModel('Divisions');
        $this->loadModel('MemberStatuses');
        $this->loadModel('Regions');

        $this->Panel->setHeadingValue('action', '');
        $this->Panel->setHeadingValue('title', 'Client Details');

        $member = $this->Members->get($id, [
            'contain' => ['Division', 'MemberDetails', 'MemberContacts', 'MemberAddresses' => ['Countries', 'Regions'], 'MemberNotes', 'MemberStatuses'],
        ]);
        if ($member) {
            $member->full_name = $member->full_name;
        }
        if ($this->is(['patch', 'post', 'put'])) {
            $data = $this->getData();
            $tab = explode(' ', $data['full_name']);
            $data['first_name'] = array_shift($tab);
            $data['last_name'] = implode(' ', $tab);
            $member = $this->Members->patchEntity($member, $data, [
                'associated' => [
                    'Division',
                    'Consultants',
                    'MemberAddresses' => ['Countries', 'Regions'],
                    'MemberContacts',
                    'MemberDetails',
                    'MemberNotes',
                    'MemberStatuses',
                ],
            ]);

            if ($this->Members->save($member)) {
                $this->dispatchEvent('Member.update', [$member]);
                $this->Flash->success(__('The client has been saved.'));

                return $this->redirect(['action' => 'edit', $id]);
            }
            $this->Flash->error(__('The client could not be saved. Please, try again.'));
        }

        $consultants = $this->Members->Consultants->find('list', [
            'keyField' => 'id',
            'valueField' => 'full_name',
        ]);
        $countries = $this->Countries->find('list');
        $memberStatuses = $this->MemberStatuses->find('list');
        $areas = $this->Areas->find('list')->orderAsc('name');
        $regions = $this->Regions->find('list')->orderAsc('name');
        $divisions = $this->Divisions->find('list')->orderAsc('name');

        $this->set(compact('member', 'consultants', 'countries', 'memberStatuses', 'areas', 'regions', 'divisions'));

        return null;
    }
}
